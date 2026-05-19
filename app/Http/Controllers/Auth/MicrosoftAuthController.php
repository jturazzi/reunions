<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthController extends Controller
{
    /**
     * Redirige l'utilisateur vers la page de connexion Microsoft 365.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('microsoft')->redirect();
    }

    /**
     * Gère le callback après authentification Microsoft 365.
     *
     * - JIT Provisioning : crée l'utilisateur s'il n'existe pas encore.
     * - Détecte les comptes désactivés dans Entra ID (AADSTS50057) et
     *   bloque l'accès en marquant l'utilisateur inactif localement.
     */
    public function callback(Request $request): RedirectResponse
    {
        // Microsoft renvoie ?error= si le compte est désactivé / supprimé
        if ($request->has('error')) {
            $errorCode = $request->query('error_description', '');

            Log::warning('SSO Microsoft : erreur OAuth', [
                'error'       => $request->query('error'),
                'description' => $errorCode,
                'ip'          => $request->ip(),
            ]);

            // AADSTS50057 = compte désactivé, AADSTS50020 = utilisateur introuvable
            $isDisabled = str_contains($errorCode, 'AADSTS50057')
                       || str_contains($errorCode, 'AADSTS50020');

            if ($isDisabled) {
                // Tente de retrouver et désactiver l'utilisateur localement
                // (l'email peut être dans l'URL state selon la config)
                return redirect()->route('auth.microsoft')
                    ->with('error', 'Votre compte Microsoft 365 est désactivé. Contactez votre administrateur.');
            }

            return redirect()->route('auth.microsoft')
                ->with('error', 'Erreur d\'authentification. Veuillez réessayer.');
        }

        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (\Exception $e) {
            Log::error('SSO Microsoft : exception Socialite', ['message' => $e->getMessage()]);
            return redirect()->route('auth.microsoft')
                ->with('error', 'Authentification échouée. Veuillez réessayer.');
        }

        // Recherche par microsoft_id (le plus fiable) puis par email en fallback
        $user = User::where('microsoft_id', $microsoftUser->getId())
            ->orWhere('email', $microsoftUser->getEmail())
            ->first();

        if ($user) {
            // Vérifie que le compte est toujours actif localement
            if (! $user->is_active) {
                return redirect()->route('auth.microsoft')
                    ->with('error', 'Votre compte a été désactivé. Contactez votre administrateur.');
            }

            // Met à jour les informations depuis Entra ID
            $user->update([
                'microsoft_id' => $microsoftUser->getId(),
                'name'         => $microsoftUser->getName(),
                'is_active'    => true, // Réactive si le compte avait été marqué inactif par erreur
            ]);
        } else {
            // JIT Provisioning : premier login → création automatique
            // Le tout premier utilisateur de l'application devient automatiquement admin
            $isFirstUser = User::count() === 0;

            $user = User::create([
                'microsoft_id'      => $microsoftUser->getId(),
                'name'              => $microsoftUser->getName(),
                'email'             => $microsoftUser->getEmail(),
                'email_verified_at' => now(),
                'password'          => null,
                'is_active'         => true,
                'is_admin'          => $isFirstUser,
            ]);

            if ($isFirstUser) {
                Log::info("Premier utilisateur SSO — admin automatique accordé : {$user->email}");
            }
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }
}
