<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\User;
use App\Observers\EventObserver;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Lier MicrosoftGraphService au container avec la config correcte
        $this->app->singleton(MicrosoftGraphService::class, fn () => MicrosoftGraphService::make());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enregistrement du provider Microsoft Socialite
        $this->app['events']->listen(
            SocialiteWasCalled::class,
            \SocialiteProviders\Microsoft\MicrosoftExtendSocialite::class.'@handle'
        );

        // Gate pour restreindre l'accès admin
        Gate::define('admin', fn (User $user) => $user->is_admin);

        // Enregistrement de l'Observer sur le modèle Event
        Event::observe(EventObserver::class);
    }
}
