<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        return Inertia::render('Admin/System/Index', [
            'info' => $info,
        ]);
    }
}
