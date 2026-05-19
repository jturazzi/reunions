<?php

namespace App\Jobs;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Orchestre la synchronisation Graph Calendar pour TOUS les destinataires d'un événement.
 * Dispatche N × SyncGraphCalendarForRecipientJob avec un délai croissant
 * pour respecter les limites de l'API (configurable via GRAPH_SYNC_DELAY_SECONDS).
 *
 * @param int    $eventId  ID de l'événement
 * @param string $action   'create' | 'update' | 'delete'
 */
class SyncGraphCalendarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 30;

    public function __construct(
        public readonly int    $eventId,
        public readonly string $action,
    ) {}

    public function handle(): void
    {
        $event = Event::withTrashed()
            ->with('eventType.distributionLists.recipients')
            ->find($this->eventId);

        if (! $event || ! $event->eventType) {
            Log::info("SyncGraphCalendarJob : événement #{$this->eventId} introuvable ou sans type.");
            return;
        }

        $recipients = $event->eventType->distributionLists
            ->flatMap(fn ($list) => $list->recipients)
            ->unique('email');

        if ($recipients->isEmpty()) {
            Log::info("SyncGraphCalendarJob : aucun destinataire pour l'événement #{$this->eventId}.");
            return;
        }

        $delaySeconds = config('services.microsoft.graph_delay_seconds', 2);
        $delay        = 0;

        foreach ($recipients as $recipient) {
            SyncGraphCalendarForRecipientJob::dispatch(
                $this->eventId,
                $recipient->email,
                $recipient->name,
                $this->action,
            )->delay(now()->addSeconds($delay));

            $delay += $delaySeconds;
        }

        Log::info("SyncGraphCalendarJob : {$recipients->count()} job(s) dispatché(s) pour l'événement #{$this->eventId} (action: {$this->action}).");
    }
}
