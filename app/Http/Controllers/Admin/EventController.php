<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Jobs\SyncGraphCalendarForRecipientJob;
use App\Models\Event;
use App\Models\EventType;
use App\Models\GraphCalendarEntry;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        $eventTypes = EventType::with(['events' => function ($query) {
            $query->with('creator')->orderByDesc('start_at');
        }])->get()
            ->sortByDesc(fn ($et) => optional($et->events->first())->start_at)
            ->values();

        $totalEvents = $eventTypes->sum(fn ($et) => $et->events->count());

        // Pré-charger le statut Graph Calendar pour tous les événements
        $eventIds       = $eventTypes->flatMap(fn ($et) => $et->events->pluck('id'));
        $graphSummaries = GraphCalendarEntry::whereIn('event_id', $eventIds)
            ->selectRaw('event_id, status, COUNT(*) as total')
            ->groupBy('event_id', 'status')
            ->get()
            ->groupBy('event_id');

        $eventTypes = $eventTypes->map(fn ($type) => [
            'id'          => $type->id,
            'name'        => $type->name,
            'description' => $type->description,
            'events'      => $type->events->map(function ($event) use ($graphSummaries) {
                $rows    = $graphSummaries->get($event->id, collect());
                $synced  = $rows->firstWhere('status', 'synced');
                $failed  = $rows->firstWhere('status', 'failed');
                $pending = $rows->firstWhere('status', 'pending');

                return [
                    'id'           => $event->id,
                    'title'        => $event->title,
                    'description'  => $event->description,
                    'location'     => $event->location,
                    'date_display' => $event->start_at->translatedFormat('d M Y'),
                    'time_range'   => $event->start_at->format('H\hi') . ' → ' . $event->end_at->format('H\hi'),
                    'creator'      => $event->creator ? ['name' => $event->creator->name] : null,
                    'graph_summary' => [
                        'synced_count'  => $synced?->total ?? 0,
                        'failed_count'  => $failed?->total ?? 0,
                        'pending_count' => $pending?->total ?? 0,
                    ],
                ];
            })->values(),
        ]);

        return Inertia::render('Admin/Events/Index', [
            'eventTypes'  => $eventTypes,
            'totalEvents' => $totalEvents,
        ]);
    }

    public function create(): Response
    {
        $eventTypes = EventType::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Events/Create', [
            'eventTypes' => $eventTypes,
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Event::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        ActivityLogger::log('Événement créé', ['title' => $validated['title']]);

        return redirect()->route('admin.events.index')
            ->with('success', 'Événement créé. Les calendriers Outlook seront mis à jour automatiquement.');
    }

    public function show(Event $event): Response
    {
        $event->load('eventType', 'creator');

        $calendarEntries = GraphCalendarEntry::where('event_id', $event->id)
            ->orderBy('recipient_name')
            ->get()
            ->map(fn ($entry) => [
                'id'              => $entry->id,
                'recipient_name'  => $entry->recipient_name,
                'recipient_email' => $entry->recipient_email,
                'graph_event_id'  => $entry->graph_event_id,
                'status'          => $entry->status,
                'error_message'   => $entry->error_message,
                'synced_at'       => $entry->synced_at?->toIso8601String(),
            ])->values();

        return Inertia::render('Admin/Events/Show', [
            'event' => [
                'id'           => $event->id,
                'title'        => $event->title,
                'description'  => $event->description,
                'location'     => $event->location,
                'date_display' => $event->start_at->translatedFormat('d M Y'),
                'time_range'   => $event->start_at->format('H\hi') . ' → ' . $event->end_at->format('H\hi'),
                'event_type'   => $event->eventType ? ['id' => $event->eventType->id, 'name' => $event->eventType->name] : null,
                'creator'      => $event->creator ? ['name' => $event->creator->name] : null,
            ],
            'calendarEntries' => $calendarEntries,
        ]);
    }

    public function resendInvitation(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        // Décider de l'action : 'update' si déjà synced, 'create' sinon
        $entry  = GraphCalendarEntry::where('event_id', $event->id)
            ->where('recipient_email', $data['email'])
            ->first();
        $action = ($entry && $entry->isSynced()) ? 'update' : 'create';

        SyncGraphCalendarForRecipientJob::dispatch($event->id, $data['email'], $data['name'], $action);

        ActivityLogger::log('Resync Graph calendrier', [
            'event_id'  => $event->id,
            'recipient' => $data['email'],
            'action'    => $action,
        ]);

        return back()->with('success', "Synchronisation calendrier relancée pour {$data['email']}.");
    }

    public function edit(Event $event): Response
    {
        $eventTypes = EventType::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Events/Edit', [
            'event'      => array_merge($event->toArray(), [
                'start_at_input' => $event->start_at?->format('Y-m-d\TH:i'),
                'end_at_input'   => $event->end_at?->format('Y-m-d\TH:i'),
            ]),
            'eventTypes' => $eventTypes,
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $validated = $request->validated();

        $event->update($validated);

        ActivityLogger::log('Événement modifié', ['title' => $event->title, 'id' => $event->id]);

        return redirect()->route('admin.events.index')
            ->with('success', 'Événement mis à jour.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();
        ActivityLogger::log('Événement supprimé', ['title' => $event->title, 'id' => $event->id]);

        return redirect()->route('admin.events.index')
            ->with('success', 'Événement supprimé. Les annulations ont été envoyées aux destinataires.');
    }
}
