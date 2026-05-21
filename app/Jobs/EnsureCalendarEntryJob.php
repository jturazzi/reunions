<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\GraphCalendarEntry;
use App\Services\MicrosoftGraphService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Vérifie si l'événement Outlook du destinataire existe encore.
 *  - S'il existe  → met à jour les données (titre, date, lieu…).
 *  - S'il est absent (ou n'a jamais été créé) → le crée.
 *
 * Un seul job remplace le duo Vérifier + Resynchroniser.
 */
class EnsureCalendarEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public array $backoff = [60, 180, 600];

    public function __construct(
        public readonly int    $eventId,
        public readonly string $recipientEmail,
        public readonly string $recipientName,
    ) {}

    public function handle(MicrosoftGraphService $graph): void
    {
        $event = Event::withTrashed()->find($this->eventId);

        if (! $event || $event->trashed()) {
            Log::info('EnsureCalendarEntryJob : événement introuvable ou supprimé.', [
                'event_id' => $this->eventId,
                'email'    => $this->recipientEmail,
            ]);
            return;
        }

        $entry = GraphCalendarEntry::firstOrCreate(
            ['event_id' => $this->eventId, 'recipient_email' => $this->recipientEmail],
            ['recipient_name' => $this->recipientName, 'status' => 'pending'],
        );

        // ── Cas 1 : on a un graph_event_id → vérifier l'existence dans Outlook ─

        if ($entry->graph_event_id) {
            $exists = $graph->calendarEventExists($this->recipientEmail, $entry->graph_event_id);

            if ($exists) {
                // L'événement est présent → on le met à jour pour s'assurer que
                // les données (titre, date, lieu…) sont bien synchronisées.
                try {
                    $graph->updateCalendarEvent($this->recipientEmail, $entry->graph_event_id, $event);

                    $entry->update([
                        'status'         => 'synced',
                        'error_message'  => null,
                        'recipient_name' => $this->recipientName,
                        'synced_at'      => now(),
                    ]);

                    Log::info('EnsureCalendarEntryJob : événement présent → mis à jour.', [
                        'event_id' => $this->eventId,
                        'email'    => $this->recipientEmail,
                    ]);
                } catch (\Throwable $e) {
                    $entry->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
                    throw $e;
                }

                return;
            }

            // Absent de Outlook → on efface l'ancien ID et on recrée
            Log::info('EnsureCalendarEntryJob : événement absent de Outlook → recréation.', [
                'event_id'       => $this->eventId,
                'email'          => $this->recipientEmail,
                'old_graph_id'   => $entry->graph_event_id,
            ]);

            $entry->update(['graph_event_id' => null, 'status' => 'pending', 'error_message' => null]);
        }

        // ── Cas 2 : pas de graph_event_id → créer l'événement dans Outlook ────

        try {
            $graphEventId = $graph->createCalendarEvent($this->recipientEmail, $event);

            $entry->update([
                'graph_event_id' => $graphEventId,
                'status'         => 'synced',
                'error_message'  => null,
                'recipient_name' => $this->recipientName,
                'synced_at'      => now(),
            ]);

            Log::info('EnsureCalendarEntryJob : événement créé dans Outlook.', [
                'event_id'     => $this->eventId,
                'email'        => $this->recipientEmail,
                'graph_event_id' => $graphEventId,
            ]);
        } catch (\Throwable $e) {
            $entry->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }
}
