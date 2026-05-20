<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CheckCalendarEntryJob;
use App\Jobs\SyncGraphCalendarForRecipientJob;
use App\Models\GraphCalendarEntry;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RecipientController extends Controller
{
    /**
     * Affiche tous les événements d'un destinataire avec leur statut de sync.
     */
    public function show(string $email): Response
    {
        $entries = GraphCalendarEntry::with(['event.eventType'])
            ->where('recipient_email', $email)
            ->orderByDesc(
                \App\Models\Event::select('start_at')
                    ->whereColumn('events.id', 'graph_calendar_entries.event_id')
                    ->limit(1)
            )
            ->get();

        $name = $entries->first()?->recipient_name ?? $email;

        return Inertia::render('Admin/Recipients/Show', [
            'email'   => $email,
            'name'    => $name,
            'entries' => $entries->map(fn ($e) => [
                'id'            => $e->id,
                'event_id'      => $e->event_id,
                'event_title'   => $e->event?->title ?? '(événement supprimé)',
                'event_start'   => $e->event?->start_at?->toIso8601String(),
                'event_end'     => $e->event?->end_at?->toIso8601String(),
                'event_type'    => $e->event?->eventType?->name,
                'event_deleted' => $e->event === null,
                'status'        => $e->status,
                'error_message' => $e->error_message,
                'synced_at'     => $e->synced_at?->toIso8601String(),
                'graph_event_id'=> $e->graph_event_id,
            ]),
        ]);
    }

    /**
     * Remet en file de sync tous les événements actifs du destinataire.
     * Le job gère automatiquement : update si déjà présent, recréation si absent.
     */
    public function resync(string $email): RedirectResponse
    {
        $entries = GraphCalendarEntry::with('event')
            ->where('recipient_email', $email)
            ->whereHas('event')   // exclut les événements soft-deletés
            ->get();

        foreach ($entries as $entry) {
            SyncGraphCalendarForRecipientJob::dispatch(
                $entry->event_id,
                $email,
                $entry->recipient_name,
                'update',
            );
        }

        return back()->with('success', count($entries) . ' événements mis en file de resynchronisation.');
    }

    /**
     * Envoie en queue la vérification de chaque événement synchronisé dans Outlook.
     */
    public function checkCalendar(string $email): RedirectResponse
    {
        $entries = GraphCalendarEntry::where('recipient_email', $email)
            ->whereNotNull('graph_event_id')
            ->get();

        if ($entries->isEmpty()) {
            return back()->with('success', 'Aucun événement synchronisé à vérifier.');
        }

        foreach ($entries as $entry) {
            CheckCalendarEntryJob::dispatch($entry->id);
        }

        return back()->with('success', $entries->count() . ' vérification(s) lancée(s) en arrière-plan. Rechargez la page dans quelques instants.');
    }
}
