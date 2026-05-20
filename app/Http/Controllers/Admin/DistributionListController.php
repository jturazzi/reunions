<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncGraphCalendarForRecipientJob;
use App\Models\DistributionList;
use App\Models\DistributionListRecipient;
use App\Services\ActivityLogger;
use App\Services\MicrosoftGraphService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DistributionListController extends Controller
{
    public function index(): Response
    {
        $lists = DistributionList::withCount(['recipients', 'eventTypes'])
            ->orderBy('name')
            ->get()
            ->map(fn ($list) => [
                'id'                => $list->id,
                'name'              => $list->name,
                'description'       => $list->description,
                'recipients_count'  => $list->recipients_count,
                'event_types_count' => $list->event_types_count,
            ]);

        return Inertia::render('Admin/DistributionLists/Index', ['lists' => $lists]);
    }

    public function show(DistributionList $distributionList): Response
    {
        try {
            $msUsers = MicrosoftGraphService::make()->getAllUsers();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('getAllUsers échoué', ['error' => $e->getMessage()]);
            $msUsers = [];
        }

        return Inertia::render('Admin/DistributionLists/Show', [
            'list'       => [
                'id'          => $distributionList->id,
                'name'        => $distributionList->name,
                'description' => $distributionList->description,
            ],
            'recipients' => $distributionList->recipients->map(fn ($r) => [
                'id'    => $r->id,
                'name'  => $r->name,
                'email' => $r->email,
            ]),
            'msUsers' => $msUsers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/DistributionLists/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $list = DistributionList::create($data);
        ActivityLogger::log('Liste de distribution créée', ['name' => $list->name]);

        return redirect()->route('admin.distribution-lists.show', $list)
            ->with('success', 'Groupe créé. Vous pouvez maintenant ajouter des membres.');
    }

    public function destroy(DistributionList $distributionList): RedirectResponse
    {
        if ($distributionList->eventTypes()->exists()) {
            return back()->with('error', "Impossible de supprimer \"{$distributionList->name}\" : ce groupe est affecté à un ou plusieurs types d'événements. Désaffectez-le d'abord.");
        }

        ActivityLogger::log('Liste de distribution supprimée', [
            'name' => $distributionList->name,
            'id'   => $distributionList->id,
        ]);
        $distributionList->delete();

        return redirect()->route('admin.distribution-lists.index')
            ->with('success', 'Groupe supprimé.');
    }

    /**
     * Ajoute un destinataire à la liste et envoie les invitations
     * pour tous les événements futurs liés aux types associés à cette liste.
     */
    public function storeRecipient(Request $request, DistributionList $distributionList): RedirectResponse
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $recipient = $distributionList->recipients()->firstOrCreate(
            ['email' => $data['email']],
            ['name'  => $data['name']],
        );

        // Si le destinataire vient d'être créé, l'ajouter dans les calendriers des événements futurs
        if ($recipient->wasRecentlyCreated) {
            $distributionList->load('eventTypes');
            $eventTypeIds = $distributionList->eventTypes->pluck('id');

            $futureEvents = \App\Models\Event::whereIn('event_type_id', $eventTypeIds)
                ->where('start_at', '>', now())
                ->get();

            $delay = 0;
            foreach ($futureEvents as $event) {
                SyncGraphCalendarForRecipientJob::dispatch(
                    $event->id,
                    $recipient->email,
                    $recipient->name,
                    'create',
                )->delay(now()->addSeconds($delay));
                $delay += config('services.microsoft.graph_delay_seconds', 2);
            }
        }

        return redirect()->route('admin.distribution-lists.show', $distributionList)
            ->with('success', 'Destinataire ajouté.');
    }

    /**
     * Supprime un destinataire de la liste et lui envoie un CANCEL pour tous les événements liés.
     */
    public function destroyRecipient(DistributionList $distributionList, DistributionListRecipient $recipient): RedirectResponse
    {
        abort_if($recipient->distribution_list_id !== $distributionList->id, 403);

        // Supprimer des calendriers Outlook avant de supprimer de la liste
        $distributionList->load('eventTypes');
        $eventTypeIds = $distributionList->eventTypes->pluck('id');

        $events = \App\Models\Event::whereIn('event_type_id', $eventTypeIds)->get();

        $delay = 0;
        foreach ($events as $event) {
            SyncGraphCalendarForRecipientJob::dispatch(
                $event->id,
                $recipient->email,
                $recipient->name,
                'delete',
            )->delay(now()->addSeconds($delay));
            $delay += config('services.microsoft.graph_delay_seconds', 2);
        }

        $recipient->delete();

        return redirect()->route('admin.distribution-lists.show', $distributionList)
            ->with('success', 'Destinataire supprimé. Les calendriers seront mis à jour.');
    }

    /**
     * Recherche des utilisateurs dans l'annuaire Microsoft Azure AD.
     */
    public function searchMsUsers(Request $request): JsonResponse
    {
        $query = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']])['q'];

        try {
            $users = MicrosoftGraphService::make()->searchUsers($query);
            return response()->json(['users' => $users]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }
}
