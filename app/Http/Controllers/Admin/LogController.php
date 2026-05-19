<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function index(): Response
    {
        $logPath = storage_path('logs/activity.log');
        $entries = [];

        if (file_exists($logPath)) {
            $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.+?) (\{.*\})?$/';

            foreach (array_reverse($lines) as $line) {
                if (preg_match($pattern, $line, $m)) {
                    $context = [];
                    if (!empty($m[4])) {
                        $context = json_decode($m[4], true) ?? [];
                    }
                    $entries[] = [
                        'date'    => $m[1],
                        'level'   => strtolower($m[2]),
                        'message' => $m[3],
                        'context' => $context,
                    ];
                }
            }
        }

        $page    = max(1, (int) request('page', 1));
        $perPage = 50;
        $total   = count($entries);
        $entries = array_slice($entries, ($page - 1) * $perPage, $perPage);

        return Inertia::render('Admin/Logs/Index', compact('entries', 'total', 'page', 'perPage'));
    }
}
