<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
     * Enregistre une action admin dans le fichier activity.log.
     *
     * @param  string  $action  Description courte (ex: "Événement créé")
     * @param  array   $context Données supplémentaires
     */
    public static function log(string $action, array $context = []): void
    {
        $user = Auth::user();

        Log::channel('activity')->info($action, array_merge([
            'user'  => $user?->name ?? 'Système',
            'email' => $user?->email ?? '—',
            'ip'    => request()->ip(),
        ], $context));
    }
}
