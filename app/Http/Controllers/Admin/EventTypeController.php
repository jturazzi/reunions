<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventTypeRequest;
use App\Jobs\SyncGraphCalendarForRecipientJob;
use App\Models\DistributionList;
use App\Models\Event;
use App\Models\EventType;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventTypeController extends Controller
{
    public function index(): Response
    {
        $eventTypes = EventType::withCount('events')->orderBy('name')->get()->map(fn ($t) => [
            'id'           => $t->id,
            'name'         => $t->name,
            'description'  => $t->description,
            'events_count' => $t->events_count,
        ]);

        return Inertia::render('Admin/EventTypes/Index', ['eventTypes' => $eventTypes]);
    }

    public function show(EventType $eventType): Response
    {
        $eventType->load('distributionLists.recipients');

        $allLists = DistributionList::orderBy('name')->get(['id', 'name', 'description']);
        $assignedIds = $eventType->distributionLists->pluck('id')->toArray();

        return Inertia::render('Admin/EventTypes/Show', [
            'eventType'   => [
                'id'          => $eventType->id,
                'name'        => $eventType->name,
                'description' => $eventType->description,
                'distribution_lists' => $eventType->distributionLists->map(fn ($l) => [
                    'id'              => $l->id,
                    'name'            => $l->name,
                    'description'     => $l->description,
                    'recipients_count'=> $l->recipients->count(),
                ]),
            ],
            'allLists'    => $allLists,
            'assignedIds' => $assignedIds,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/EventTypes/Create');
    }

    public function store(StoreEventTypeRequest $request): RedirectResponse
    {
        EventType::create($request->validated());
        ActivityLogger::log("Type d'événement créé", ['name' => $request->input('name')]);

        return redirect()->route('admin.event-types.index')
            ->with('success', "Type d'événement créé avec succès.");
    }

    public function attachDistributionList(Request $request, EventType $eventType): RedirectResponse
    {
        $request->validate(['distribution_list_id' => 'required|exists:distribution_lists,id']);

        $listId = (int) $request->distribution_list_id;
        $eventType->distributionLists()->syncWithoutDetaching([$listId]);

        ActivityLogger::log("Liste assignée à un type d'événement", [
            'event_type' => $eventType->name,
            'list_id'    => $listId,
        ]);

        // Créer les entrées Graph pour les événements futurs de ce type
        $list = DistributionList::with('recipients')->find($listId);
        $futureEvents = Event::where('event_type_id', $eventType->id)
            ->where('start_at', '>', now())
            ->get();

        $delay = 0;
        foreach ($futureEvents as $event) {
            foreach ($list->recipients as $recipient) {
                SyncGraphCalendarForRecipientJob::dispatch(
                    $event->id,
                    $recipient->email,
                    $recipient->name,
                    'create',
                )->delay(now()->addSeconds($delay));
                $delay += config('services.microsoft.graph_delay_seconds', 2);
            }
        }

        return back()->with('success', 'Liste de distribution assignée.');
    }

    public function detachDistributionList(EventType $eventType, DistributionList $distributionList): RedirectResponse
    {
        // Supprimer des calendriers Graph les événements du type pour les destinataires de cette liste
        $distributionList->load('recipients');
        $events = Event::where('event_type_id', $eventType->id)->get();

        $delay = 0;
        foreach ($events as $event) {
            foreach ($distributionList->recipients as $recipient) {
                SyncGraphCalendarForRecipientJob::dispatch(
                    $event->id,
                    $recipient->email,
                    $recipient->name,
                    'delete',
                )->delay(now()->addSeconds($delay));
                $delay += config('services.microsoft.graph_delay_seconds', 2);
            }
        }

        $eventType->distributionLists()->detach($distributionList->id);

        ActivityLogger::log("Liste retirée d'un type d'événement", [
            'event_type' => $eventType->name,
            'list_id'    => $distributionList->id,
        ]);

        return back()->with('success', 'Liste de distribution retirée.');
    }

    public function destroy(EventType $eventType): RedirectResponse
    {
        ActivityLogger::log("Type d'événement supprimé", ['name' => $eventType->name, 'id' => $eventType->id]);
        $eventType->delete();

        return redirect()->route('admin.event-types.index')
            ->with('success', "Type d'événement supprimé.");
    }
}
