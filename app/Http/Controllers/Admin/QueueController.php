<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\EnsureCalendarEntryJob;
use App\Jobs\SyncGraphCalendarForRecipientJob;
use App\Jobs\SyncGraphCalendarJob;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QueueController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Queue/Index', [
            'initialStats' => $this->buildStats(),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->buildStats());
    }

    // ─── Parsing du payload sérialisé ─────────────────────────────────────────

    /**
     * Extrait les propriétés métier d'un job à partir de son payload JSON.
     * Retourne : job (nom court), event_id, recipient_email, recipient_name, action, entry_id
     */
    private function parsePayload(string $rawPayload): array
    {
        $result = [
            'job'             => '?',
            'event_id'        => null,
            'recipient_email' => null,
            'recipient_name'  => null,
            'action'          => null,
            'entry_id'        => null,
        ];

        $payload = json_decode($rawPayload, true);
        if (! $payload) {
            return $result;
        }

        $className = $payload['displayName'] ?? $payload['data']['commandName'] ?? '';
        $result['job'] = class_basename(str_replace('\\', '/', $className));

        $serialized = $payload['data']['command'] ?? '';
        if (! $serialized) {
            return $result;
        }

        try {
            $obj = unserialize($serialized, [
                'allowed_classes' => [
                    SyncGraphCalendarForRecipientJob::class,
                    SyncGraphCalendarJob::class,
                    EnsureCalendarEntryJob::class,
                ],
            ]);

            if ($obj instanceof SyncGraphCalendarForRecipientJob) {
                $result['event_id']        = $obj->eventId;
                $result['recipient_email'] = $obj->recipientEmail;
                $result['recipient_name']  = $obj->recipientName;
                $result['action']          = $obj->action;
            } elseif ($obj instanceof SyncGraphCalendarJob) {
                $result['event_id'] = $obj->eventId;
                $result['action']   = $obj->action;
            } elseif ($obj instanceof EnsureCalendarEntryJob) {
                $result['event_id']        = $obj->eventId;
                $result['recipient_email'] = $obj->recipientEmail;
                $result['recipient_name']  = $obj->recipientName;
            }
        } catch (\Throwable) {
            // payload corrompu — on garde les valeurs par défaut
        }

        return $result;
    }

    // ─── Enrichissement en lot ────────────────────────────────────────────────

    /**
     * À partir d'une collection de jobs parsés, résout les titres d'événements
     * et les destinataires pour les CheckCalendarEntryJob.
     *
     * @param array $jobs  [['event_id'=>…, 'entry_id'=>…, …], …]
     */
    private function enrichJobs(array $jobs): array
    {
        // 1. Titres d'événements
        $eventIds = array_filter(array_unique(array_column($jobs, 'event_id')));
        $events   = $eventIds
            ? DB::table('events')->whereIn('id', $eventIds)->pluck('title', 'id')->all()
            : [];

        // 2. graph_calendar_entries pour EnsureCalendarEntryJob (résolution via entry_id si présent)
        $entryIds = array_filter(array_unique(array_column($jobs, 'entry_id')));
        $entries  = [];
        if ($entryIds) {
            $entries = DB::table('graph_calendar_entries')
                ->whereIn('id', $entryIds)
                ->select('id', 'event_id', 'recipient_email', 'recipient_name')
                ->get()
                ->keyBy('id')
                ->all();

            // Compléter les titres manquants depuis les entries
            $missingEventIds = array_filter(array_unique(
                array_map(fn ($e) => $e->event_id, $entries)
            ));
            $missingEventIds = array_diff($missingEventIds, array_keys($events));
            if ($missingEventIds) {
                $extra  = DB::table('events')->whereIn('id', $missingEventIds)->pluck('title', 'id')->all();
                $events = array_merge($events, $extra);
            }
        }

        return array_map(function (array $job) use ($events, $entries) {
            // Pour EnsureCalendarEntryJob passé via entry_id : récupérer email/event via l'entry
            if ($job['entry_id'] !== null && isset($entries[$job['entry_id']])) {
                $entry                  = $entries[$job['entry_id']];
                $job['recipient_email'] = $job['recipient_email'] ?? $entry->recipient_email;
                $job['recipient_name']  = $job['recipient_name']  ?? $entry->recipient_name;
                $job['event_id']        = $job['event_id']        ?? $entry->event_id;
            }

            $job['event_title'] = isset($job['event_id'], $events[$job['event_id']])
                ? $events[$job['event_id']]
                : ($job['event_id'] ? '#' . $job['event_id'] : null);

            return $job;
        }, $jobs);
    }

    // ─── Statistiques principales ─────────────────────────────────────────────

    private function buildStats(): array
    {
        $pending    = DB::table('jobs')->whereNull('reserved_at')->count();
        $processing = DB::table('jobs')->whereNotNull('reserved_at')->count();
        $failed     = DB::table('failed_jobs')->count();

        // Répartition par queue
        $queues = DB::table('jobs')
            ->select(
                'queue',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN reserved_at IS NOT NULL THEN 1 ELSE 0 END) as processing')
            )
            ->groupBy('queue')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($q) => [
                'name'       => $q->queue,
                'total'      => (int) $q->total,
                'processing' => (int) $q->processing,
                'pending'    => (int) $q->total - (int) $q->processing,
            ])
            ->values()
            ->toArray();

        // 50 jobs les plus récents — parsés puis enrichis
        $rawJobs = DB::table('jobs')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $recentJobs = $rawJobs->map(function ($job) {
            $parsed = $this->parsePayload($job->payload);
            return array_merge($parsed, [
                'id'         => $job->id,
                'queue'      => $job->queue,
                'attempts'   => $job->attempts,
                'processing' => ! is_null($job->reserved_at),
                'created_at' => Carbon::createFromTimestamp($job->created_at)->format('H:i:s'),
                'event_title' => null,
            ]);
        })->values()->toArray();

        $recentJobs = $this->enrichJobs($recentJobs);

        // 10 derniers jobs en échec — parsés puis enrichis
        $rawFailed = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(10)
            ->get();

        $recentFailed = $rawFailed->map(function ($job) {
            $parsed        = $this->parsePayload($job->payload);
            $exceptionLine = strtok($job->exception ?? '', "\n");
            return array_merge($parsed, [
                'id'          => $job->id,
                'queue'       => $job->queue,
                'exception'   => $exceptionLine,
                'failed_at'   => Carbon::parse($job->failed_at)->format('H:i:s'),
                'event_title' => null,
            ]);
        })->values()->toArray();

        $recentFailed = $this->enrichJobs($recentFailed);

        // Batches actifs (non terminés, non annulés)
        $batches = DB::table('job_batches')
            ->whereNull('finished_at')
            ->whereNull('cancelled_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($b) => [
                'id'           => $b->id,
                'name'         => $b->name,
                'total_jobs'   => $b->total_jobs,
                'pending_jobs' => $b->pending_jobs,
                'failed_jobs'  => $b->failed_jobs,
                'progress'     => $b->total_jobs > 0
                    ? round(($b->total_jobs - $b->pending_jobs) / $b->total_jobs * 100)
                    : 0,
                'created_at' => Carbon::createFromTimestamp($b->created_at)->format('H:i:s'),
            ])
            ->values()
            ->toArray();

        return [
            'pending'       => $pending,
            'processing'    => $processing,
            'failed'        => $failed,
            'total'         => $pending + $processing,
            'queues'        => $queues,
            'recent_jobs'   => $recentJobs,
            'recent_failed' => $recentFailed,
            'batches'       => $batches,
            'refreshed_at'  => now()->format('H:i:s'),
        ];
    }
}
