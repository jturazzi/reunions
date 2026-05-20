<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Accès à Microsoft Graph API via Client Credentials (permissions Application).
 *
 * Permissions Azure requises (Application, avec consentement admin) :
 *   - Calendars.ReadWrite
 *
 * Le token OAuth2 est mis en cache 55 minutes (durée de vie réelle : 60 min).
 */
class MicrosoftGraphService
{
    private const GRAPH_BASE = 'https://graph.microsoft.com/v1.0';
    private const TOKEN_CACHE_KEY = 'ms_graph_access_token';

    public function __construct(
        private readonly string $tenantId     = '',
        private readonly string $clientId     = '',
        private readonly string $clientSecret = '',
    ) {}

    public static function make(): static
    {
        return new static(
            tenantId:     config('services.microsoft.tenant'),
            clientId:     config('services.microsoft.client_id'),
            clientSecret: config('services.microsoft.client_secret'),
        );
    }

    // -------------------------------------------------------------------------
    // Authentification
    // -------------------------------------------------------------------------

    /**
     * Retourne un access token valide (mis en cache 55 min).
     *
     * @throws \RuntimeException si l'authentification échoue
     */
    public function getAccessToken(): string
    {
        $token = Cache::remember(self::TOKEN_CACHE_KEY, 55 * 60, function () {
            try {
                $response = Http::timeout(10)->asForm()->post(
                    "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token",
                    [
                        'grant_type'    => 'client_credentials',
                        'client_id'     => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'scope'         => 'https://graph.microsoft.com/.default',
                    ]
                );
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                throw new \RuntimeException('Impossible de joindre Microsoft (connexion) : ' . $e->getMessage());
            }

            if (! $response->successful()) {
                $detail = $response->json('error_description') ?? $response->json('error') ?? $response->body();
                throw new \RuntimeException(
                    "Échec d'authentification Graph API [{$response->status()}] : {$detail}"
                );
            }

            $token = $response->json('access_token');
            if (empty($token)) {
                throw new \RuntimeException('Token Graph API vide dans la réponse Microsoft.');
            }

            return $token;
        });

        // Sécurité : ne pas retourner une valeur nulle/vide mis en cache
        if (empty($token)) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            throw new \RuntimeException('Token Graph API invalide (cache corrompu, vidé).');
        }

        return $token;
    }

    // -------------------------------------------------------------------------
    // Opérations calendrier
    // -------------------------------------------------------------------------

    /**
     * Crée un événement dans le calendrier Outlook d'un utilisateur.
     *
     * @return string  L'ID Graph de l'événement créé
     * @throws \RuntimeException
     */
    public function createCalendarEvent(string $userEmail, Event $event): string
    {
        $response = Http::withToken($this->getAccessToken())
            ->post(self::GRAPH_BASE . "/users/{$userEmail}/events", $this->buildPayload($event));

        if (! $response->successful()) {
            throw new \RuntimeException(
                "Création Graph échouée pour {$userEmail} : [{$response->status()}] " . $response->body()
            );
        }

        return $response->json('id');
    }

    /**
     * Met à jour un événement Outlook existant.
     *
     * @throws \RuntimeException
     */
    public function updateCalendarEvent(string $userEmail, string $graphEventId, Event $event): void
    {
        $response = Http::withToken($this->getAccessToken())
            ->patch(self::GRAPH_BASE . "/users/{$userEmail}/events/{$graphEventId}", $this->buildPayload($event));

        if (! $response->successful()) {
            throw new \RuntimeException(
                "Mise à jour Graph échouée pour {$userEmail} : [{$response->status()}] " . $response->body()
            );
        }
    }

    /**
     * Supprime un événement du calendrier Outlook.
     * Tolère un 404 (l'événement a peut-être déjà été supprimé côté Outlook).
     *
     * @throws \RuntimeException
     */
    public function deleteCalendarEvent(string $userEmail, string $graphEventId): void
    {
        $response = Http::withToken($this->getAccessToken())
            ->delete(self::GRAPH_BASE . "/users/{$userEmail}/events/{$graphEventId}");

        if ($response->status() === 404) {
            // Déjà supprimé côté Outlook — on considère ça OK
            return;
        }

        if (! $response->successful()) {
            throw new \RuntimeException(
                "Suppression Graph échouée pour {$userEmail} : [{$response->status()}] " . $response->body()
            );
        }
    }

    // -------------------------------------------------------------------------
    // Annuaire utilisateurs
    // -------------------------------------------------------------------------

    /**
     * Retourne tous les utilisateurs de l'annuaire Azure AD (avec pagination).
     * Résultat mis en cache 5 minutes.
     * Requiert la permission Application : User.Read.All
     *
     * @return array<int, array{name: string, email: string}>
     */
    public function getAllUsers(): array
    {
        return Cache::remember('ms_graph_all_users', 5 * 60, function () {
            $users = [];
            $url   = self::GRAPH_BASE . '/users?$select=displayName,mail,userPrincipalName&$top=999';

            do {
                $response = Http::withToken($this->getAccessToken())->get($url);

                if (! $response->successful()) {
                    throw new \RuntimeException(
                        "Récupération utilisateurs Graph échouée : [{$response->status()}] " . $response->body()
                    );
                }

                foreach ($response->json('value', []) as $user) {
                    $email = $user['mail'] ?? $user['userPrincipalName'] ?? null;
                    $name  = $user['displayName'] ?? null;

                    if ($email && $name && ! str_contains($email, '#EXT#')) {
                        $users[] = ['name' => $name, 'email' => $email];
                    }
                }

                $url = $response->json('@odata.nextLink');
            } while ($url);

            usort($users, fn ($a, $b) => strcmp($a['name'], $b['name']));

            return $users;
        });
    }

    /**
     * Vérifie si un événement existe encore dans le calendrier Outlook d'un utilisateur.
     *
     * @return bool  true = présent, false = absent (404)
     * @throws \RuntimeException pour toute erreur autre que 404
     */
    public function calendarEventExists(string $userEmail, string $graphEventId): bool
    {
        $response = Http::withToken($this->getAccessToken())
            ->get(self::GRAPH_BASE . "/users/{$userEmail}/events/{$graphEventId}?\$select=id");

        if ($response->status() === 404) {
            return false;
        }

        if (! $response->successful()) {
            throw new \RuntimeException(
                "Vérification Graph échouée pour {$userEmail} : [{$response->status()}] " . $response->body()
            );
        }

        return true;
    }

    /**
     * Recherche des utilisateurs dans l'annuaire Azure AD.
     * Requiert la permission Application : User.Read.All
     *
     * @return array<int, array{name: string, email: string}>
     */
    public function searchUsers(string $query): array
    {
        if (strlen(trim($query)) < 2) {
            return [];
        }

        $response = Http::withToken($this->getAccessToken())
            ->withHeaders(['ConsistencyLevel' => 'eventual'])
            ->get(self::GRAPH_BASE . '/users', [
                '$search'  => '"displayName:' . $query . '" OR "mail:' . $query . '" OR "userPrincipalName:' . $query . '"',
                '$select'  => 'displayName,mail,userPrincipalName',
                '$top'     => 15,
                '$orderby' => 'displayName',
                '$count'   => 'true',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "Recherche utilisateurs Graph échouée : [{$response->status()}] " . $response->body()
            );
        }

        $results = [];
        foreach ($response->json('value', []) as $user) {
            $email = $user['mail'] ?? $user['userPrincipalName'] ?? null;
            $name  = $user['displayName'] ?? null;

            if ($email && $name && ! str_contains($email, '#EXT#')) {
                $results[] = ['name' => $name, 'email' => $email];
            }
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // Diagnostic
    // -------------------------------------------------------------------------

    /**
     * Vérifie la connectivité Graph API.
     *
     * @return array{status: 'ok'|'error', message: string, latency_ms?: int}
     */
    public function checkConnection(): array
    {
        $start = microtime(true);

        try {
            // Vider le cache pour forcer un vrai appel
            Cache::forget(self::TOKEN_CACHE_KEY);
            $token = $this->getAccessToken();

            // Appel léger : récupérer les infos de l'organisation
            $response = Http::withToken($token)
                ->get(self::GRAPH_BASE . '/organization?$select=displayName,verifiedDomains&$top=1');

            $latency = (int) ((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                $orgName = $response->json('value.0.displayName', 'inconnue');
                return [
                    'status'     => 'ok',
                    'message'    => "Connecté à « {$orgName} »",
                    'latency_ms' => $latency,
                ];
            }

            return [
                'status'     => 'error',
                'message'    => "Token obtenu mais appel Graph refusé : [{$response->status()}] " . $response->body(),
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            Log::warning('Graph API checkConnection échoué', ['error' => $e->getMessage()]);
            return [
                'status'  => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Payload
    // -------------------------------------------------------------------------

    private function buildPayload(Event $event): array
    {
        $tz = config('app.timezone', 'Europe/Paris');

        $payload = [
            'subject' => $event->title,
            'start'   => [
                'dateTime' => $event->start_at->format('Y-m-d\TH:i:s'),
                'timeZone' => $tz,
            ],
            'end' => [
                'dateTime' => $event->end_at->format('Y-m-d\TH:i:s'),
                'timeZone' => $tz,
            ],
            'isReminderOn'   => true,
            'reminderMinutesBeforeStart' => 15,
        ];

        // Corps de l'événement : description + avertissement
        // Note : Outlook/Exchange ne permet pas de verrouiller la suppression côté destinataire.
        // L'avertissement ci-dessous rappelle à l'utilisateur de ne pas supprimer l'événement.
        $body = $event->description ?? '';
        $warning = "⚠️ Cet événement est géré automatiquement. Merci de ne pas le supprimer de votre calendrier.";
        $payload['body'] = [
            'contentType' => 'text',
            'content'     => $body ? $body . "\n\n" . $warning : $warning,
        ];

        if ($event->location) {
            $payload['location'] = ['displayName' => $event->location];
        }

        return $payload;
    }
}
