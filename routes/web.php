<?php

use App\Http\Controllers\Admin\DistributionListController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\EventTypeController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Authentification locale (email + mot de passe)
|--------------------------------------------------------------------------
*/

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit')->middleware('guest');

/*
|--------------------------------------------------------------------------
| Authentification Microsoft 365 (SSO)
|--------------------------------------------------------------------------
*/

Route::prefix('auth/microsoft')->name('auth.microsoft')->group(function () {
    Route::get('/', [MicrosoftAuthController::class, 'redirect'])->name('');
    Route::get('/callback', [MicrosoftAuthController::class, 'callback'])->name('.callback');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Routes protégées (utilisateurs authentifiés)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Tableau de bord utilisateur
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Administration (réservé aux admins)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->middleware('can:admin')->group(function () {
        // Types d'événements (catégories + gestion des listes assignées)
        Route::resource('event-types', EventTypeController::class)
            ->only(['index', 'show', 'create', 'store', 'destroy']);

        Route::post('event-types/{eventType}/distribution-lists', [EventTypeController::class, 'attachDistributionList'])
            ->name('event-types.distribution-lists.attach');
        Route::delete('event-types/{eventType}/distribution-lists/{distributionList}', [EventTypeController::class, 'detachDistributionList'])
            ->name('event-types.distribution-lists.detach');

        // Listes de distribution
        Route::resource('distribution-lists', DistributionListController::class)
            ->only(['index', 'show', 'create', 'store', 'destroy']);

        Route::post('distribution-lists/{distributionList}/recipients', [DistributionListController::class, 'storeRecipient'])
            ->name('distribution-lists.recipients.store');
        Route::delete('distribution-lists/{distributionList}/recipients/{recipient}', [DistributionListController::class, 'destroyRecipient'])
            ->name('distribution-lists.recipients.destroy');

        // Événements (CRUD complet)
        Route::resource('events', EventController::class)
            ->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);

        Route::post('events/{event}/resend', [EventController::class, 'resendInvitation'])
            ->name('events.resend');

        // Logs d'actions
        Route::get('logs', [LogController::class, 'index'])->name('logs.index');

        // Informations système
        Route::get('system', [SystemController::class, 'index'])->name('system.index');
    });
});

