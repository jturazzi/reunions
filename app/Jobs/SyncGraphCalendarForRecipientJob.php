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
 * Synchronise l'événement dans le calendrier Outlook d'un destinataire.
 *
 * @param int    $eventId        ID de l'événement Laravel
 * @param string $recipientEmail Email Outlook du destinataire (doit être dans le tenant M365)
 * @param string $recipientName  Nom affiché
 * @param string $action         'create' | 'update' | 'delete'
 */
class SyncGraphCalendarForRecipientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public array $backoff = [60, 180, 600];

    public function __construct(
        public readonly int    $eventId,
        public readonly string $recipientEmail,
        public readonly string $recipientName,
        public readonly string $action,           // create | update | delete
    ) {}

    public function handle(MicrosoftGraphService $graph): void
    {
        $event = Event::withTrashed()->find($this->eventId);

        if (! $event) {
            Log::warning('SyncGraphCalendarForRecipientJob : événement introuvable', [
                'event_id' => $this->eventId,
            ]);
            return;
        }

        match ($this->action) {
            'create' => $this->handleCreate($graph, $event),
            'update' => $this->handleUpdate($graph, $event),
            'delete' => $this->handleDelete($graph),
        };
    }

    // -------------------------------------------------------------------------

    private function handleCreate(MicrosoftGraphService $graph, Event $event): void
    {
        $entry = GraphCalendarEntry::firstOrCreate(
            ['event_id' => $this->eventId, 'recipient_email' => $this->recipientEmail],
            ['recipient_name' => $this->recipientName, 'status' => 'pending'],
        );

        // Si déjà synced avec un graph_event_id, faire un update
        if ($entry->isSynced()) {
            $this->handleUpdate($graph, $event, $entry);
            return;
        }

        try {
            $graphEventId = $graph->createCalendarEvent($this->recipientEmail, $event);

            $entry->update([
                'graph_event_id' => $graphEventId,
                'status'         => 'synced',
                'error_message'  => null,
                'synced_at'      => now(),
                'recipient_name' => $this->recipientName,
            ]);
        } catch (\Throwable $e) {
            $entry->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'synced_at'     => now(),
            ]);

            Log::error('Graph create échoué', [
                'event_id'  => $this->eventId,
                'recipient' => $this->recipientEmail,
                'error'     => $e->getMessage(),
            ]);

            throw $e; // déclenchera le retry
        }
    }

    private function handleUpdate(MicrosoftGraphService $graph, Event $event, ?GraphCalendarEntry $entry = null): void
    {
        $entry ??= GraphCalendarEntry::where('event_id', $this->eventId)
            ->where('recipient_email', $this->recipientEmail)
            ->first();

        if (! $entry || ! $entry->graph_event_id) {
            // Pas encore créé — on crée
            $this->handleCreate($graph, $event);
            return;
        }

        try {
            $graph->updateCalendarEvent($this->recipientEmail, $entry->graph_event_id, $event);

            $entry->update([
                'status'        => 'synced',
                'error_message' => null,
                'synced_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            // Événement supprimé côté Outlook → recréer
            if (str_contains($e->getMessage(), 'ErrorItemNotFound')) {
                Log::info('Graph update : événement introuvable, recréation', [
                    'event_id'  => $this->eventId,
                    'recipient' => $this->recipientEmail,
                ]);

                $entry->update(['graph_event_id' => null, 'status' => 'pending']);
                $this->handleCreate($graph, $event);
                return;
            }

            $entry->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'synced_at'     => now(),
            ]);

            Log::error('Graph update échoué', [
                'event_id'  => $this->eventId,
                'recipient' => $this->recipientEmail,
                'error'     => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function handleDelete(MicrosoftGraphService $graph): void
    {
        $entry = GraphCalendarEntry::where('event_id', $this->eventId)
            ->where('recipient_email', $this->recipientEmail)
            ->first();

        if (! $entry || ! $entry->graph_event_id) {
            // Rien à supprimer
            return;
        }

        try {
            $graph->deleteCalendarEvent($this->recipientEmail, $entry->graph_event_id);

            $entry->update([
                'status'        => 'deleted',
                'error_message' => null,
                'synced_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            // Déjà supprimé côté Outlook → considérer comme supprimé
            if (str_contains($e->getMessage(), 'ErrorItemNotFound')) {
                $entry->update(['status' => 'deleted', 'error_message' => null, 'synced_at' => now()]);
                return;
            }

            $entry->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'synced_at'     => now(),
            ]);

            Log::error('Graph delete échoué', [
                'event_id'  => $this->eventId,
                'recipient' => $this->recipientEmail,
                'error'     => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        // Après tous les retries, marquer définitivement comme failed
        GraphCalendarEntry::where('event_id', $this->eventId)
            ->where('recipient_email', $this->recipientEmail)
            ->update([
                'status'        => 'failed',
                'error_message' => 'Échec définitif après retries : ' . $e->getMessage(),
            ]);
    }
}
