<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\GraphCalendarEntry;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    public function index(): Response
    {
        $dbName = config('database.default');
        $dbFile = basename(config('database.connections.sqlite.database', 'database.sqlite'));

        $info = [
            'Version'          => '1.0.0',
            'Version PHP'      => PHP_VERSION,
            'Version Laravel'  => app()->version(),
            'Serveur'          => $_SERVER['SERVER_SOFTWARE'] ?? php_sapi_name(),
            'Environnement'    => app()->environment(),
            'Base de données'  => $dbName . ' — ' . $dbFile,
            'Cache'            => config('cache.default'),
            'Session'          => config('session.driver'),
            'Fuseau horaire'   => config('app.timezone'),
        ];

        $invitationStats = [
            'total'   => GraphCalendarEntry::count(),
            'synced'  => GraphCalendarEntry::where('status', 'synced')->count(),
            'pending' => GraphCalendarEntry::where('status', 'pending')->count(),
            'failed'  => GraphCalendarEntry::where('status', 'failed')->count(),
            'events'  => Event::withoutTrashed()->whereHas('eventType')->count(),
        ];

        return Inertia::render('Admin/System/Index', [
            'info'            => $info,
            'invitationStats' => $invitationStats,
        ]);
    }
}
