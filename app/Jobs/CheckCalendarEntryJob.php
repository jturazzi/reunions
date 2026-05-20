<?php

namespace App\Jobs;

use App\Models\GraphCalendarEntry;
use App\Services\MicrosoftGraphService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Vérifie en arrière-plan si un événement Graph existe encore dans le calendrier
 * Outlook du destinataire, et met à jour le statut en conséquence.
 */
class CheckCalendarEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 30;
    public array $backoff = [30, 120];

    public function __construct(
        public readonly int $entryId,
    ) {}

    public function handle(MicrosoftGraphService $graph): void
    {
        $entry = GraphCalendarEntry::find($this->entryId);

        if (! $entry || ! $entry->graph_event_id) {
            return;
        }

        try {
            $exists = $graph->calendarEventExists($entry->recipient_email, $entry->graph_event_id);
        } catch (\Throwable $e) {
            Log::warning('CheckCalendarEntryJob : erreur Graph', [
                'entry_id' => $this->entryId,
                'email'    => $entry->recipient_email,
                'error'    => $e->getMessage(),
            ]);
            throw $e; // déclenchera le retry
        }

        if ($exists) {
            // L'événement est bien dans Outlook — corriger un statut 'missing' résiduel
            if ($entry->status === 'missing') {
                $entry->update(['status' => 'synced', 'error_message' => null]);
            }
        } else {
            // Absent : vider le graph_event_id pour que le prochain resync recrée directement
            $entry->update([
                'graph_event_id' => null,
                'status'         => 'missing',
                'error_message'  => 'Absent du calendrier Outlook (vérifié le ' . now()->format('d/m/Y à H:i') . ')',
            ]);
        }
    }
}
