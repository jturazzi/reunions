<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\EnsureCalendarEntryJob;
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
     * Vérifie l'existence dans Outlook et crée si absent — combine Vérifier + Resync.
     *
     * Pour chaque entrée active :
     *   - si graph_event_id présent → vérifie dans Outlook ; si OK → met à jour ; si absent → recrée
     *   - si pas de graph_event_id → crée directement
     */
    public function ensure(string $email): RedirectResponse
    {
        $entries = GraphCalendarEntry::with('event')
            ->where('recipient_email', $email)
            ->whereHas('event')
            ->get();

        if ($entries->isEmpty()) {
            return back()->with('success', 'Aucun événement actif trouvé pour ce destinataire.');
        }

        foreach ($entries as $entry) {
            EnsureCalendarEntryJob::dispatch(
                $entry->event_id,
                $email,
                $entry->recipient_name,
            );
        }

        return back()->with('success', $entries->count() . ' job(s) de vérification/création lancé(s) en arrière-plan.');
    }
}
