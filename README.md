# Réunions — Gestion des événements agenda Outlook

Application interne pour la planification des événements.

Les événements créés sont automatiquement synchronisés dans les **agendas Outlook** des destinataires via **Microsoft Graph API**. L'authentification est déléguée à **Microsoft 365 SSO**.

---

## Fonctionnalités

### Événements
- Création, modification, suppression (titre, dates, lieu, description)
- Classement par **type d'événement**
- Synchronisation automatique dans Outlook à la création, modification et suppression
- Résynchronisation manuelle depuis la page de détail (`resend`) ou par destinataire

### Types d'événements
- Catégories réutilisables (Conseil d'administration, Assemblée générale, Bureau…)
- **Listes de distribution assignées par type** : tous les destinataires d'une liste reçoivent les événements du type correspondant

### Listes de distribution (destinataires / groupes)
- Listes nommées et réutilisables de destinataires (nom + email)
- Recherche des utilisateurs Microsoft 365 pour ajouter un destinataire
- Assignables à un ou plusieurs types d'événements
- Suppression bloquée si la liste est attachée à un type

### Suivi des destinataires
- Page par destinataire : tous ses événements avec le statut de synchronisation (`pending`, `synced`, `failed`)
- Action « Vérifier / resynchroniser » : si l'événement Outlook existe encore il est mis à jour, sinon il est recréé

### Administration
- Journal d'activité
- Suivi de la file d'attente (jobs en attente, échecs)
- Page système (version déployée, état de l'application)
- Comptes Microsoft 365 désactivés ou supprimés : l'utilisateur est automatiquement marqué `is_active = false` et ne peut plus se connecter

### Synchronisation Outlook
| Action | Effet sur les agendas |
|---|---|
| Créer un événement | Événement ajouté dans l'agenda de chaque destinataire |
| Modifier un événement | Événement mis à jour dans les agendas |
| Supprimer un événement | Événement retiré des agendas |

---

## Prérequis

- PHP **8.3+** (8.5 dans l'image Docker), Composer
- Node.js **20+**
- Tenant **Microsoft 365 / Entra ID** avec une App Registration configurée

---

## Installation

### Docker (production)

L'image est construite par GitHub Actions et publiée sur **ghcr.io** (`ghcr.io/jturazzi/reunions`). Elle embarque FrankenPHP (`serversideup/php:8.5-frankenphp-alpine`) et écoute sur le port **8080**.

```bash
docker pull ghcr.io/jturazzi/reunions:latest
```

Tags disponibles : `latest` (branche `main`), `sha-<commit>`, et les versions sémantiques (`v1.2.3`, `1.2`) pour les tags `v*`.

### Local

```bash
git clone git@github.com:jturazzi/reunions.git && cd reunions
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

---

## Configuration `.env`

```env
APP_NAME="Réunions"
APP_URL=https://votre-domaine.fr
APP_TIMEZONE=Europe/Paris
APP_LOCALE=fr

DB_CONNECTION=sqlite

# Queue (synchronisation Graph asynchrone)
QUEUE_CONNECTION=database

# App Registration Microsoft 365 (SSO + Graph API)
MICROSOFT_CLIENT_ID=xxxx
MICROSOFT_CLIENT_SECRET=xxxx
MICROSOFT_TENANT_ID=xxxx
MICROSOFT_REDIRECT_URI=https://votre-domaine.fr/auth/microsoft/callback

# Délai entre chaque appel Graph (secondes) — évite le throttling
GRAPH_SYNC_DELAY_SECONDS=2

# Jeton de la route /api/system-info (vide = route désactivée) : openssl rand -hex 32
SYSTEM_INFO_TOKEN=

# Sentry (vide = désactivé)
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0
```

---

## Configuration Azure (Entra ID)

### 1. Créer l'App Registration

1. **Azure Portal** → Entra ID → App registrations → New registration
2. Redirect URI : `https://votre-domaine.fr/auth/microsoft/callback` (type **Web**)

### 2. Permissions requises

| Permission | Type | Usage |
|---|---|---|
| `User.Read` | **Delegated** | Connexion SSO (profil utilisateur) |
| `Calendars.ReadWrite` | **Application** | Synchronisation des agendas Outlook |
| `User.Read.All` | **Application** | Recherche des utilisateurs lors de l'ajout d'un destinataire |

> ⚠️ Les permissions de type **Application** nécessitent un **Grant admin consent** dans Azure Portal.

### 3. Accorder le consentement admin

Azure Portal → App Registration → **API permissions** → **Grant admin consent for [tenant]**

### 4. Créer un Client Secret

Entra ID → App Registration → **Certificates & secrets** → New client secret → copier la valeur dans `MICROSOFT_CLIENT_SECRET`.

---

## Worker de queue

Les synchronisations Graph sont traitées via la queue Laravel. Démarrer le worker :

```bash
php artisan queue:work --sleep=3 --tries=3
```

En production avec **Supervisor** :

```ini
[program:reunions-worker]
command=php /var/www/reunions/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www/reunions
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/reunions-worker.log
```

---

## Premier administrateur

L'application crée automatiquement le compte à la première connexion SSO. Pour attribuer le rôle admin :

```bash
php artisan event:make-admin prenom.nom@votre-association.fr
```

---

## Architecture

```
app/
├── Console/Commands/
│   └── MakeAdminCommand.php                     # event:make-admin
├── Http/Controllers/Admin/
│   ├── EventController.php                      # CRUD événements + resync
│   ├── EventTypeController.php                  # CRUD types + assignation des listes
│   ├── DistributionListController.php           # CRUD listes + destinataires + recherche MS
│   ├── RecipientController.php                  # Suivi / resync par destinataire
│   ├── LogController.php                        # Journal d'activité
│   ├── QueueController.php                      # Suivi de la file d'attente
│   └── SystemController.php                     # Page système
├── Http/Controllers/Auth/
│   ├── LoginController.php                      # Formulaire de connexion
│   └── MicrosoftAuthController.php              # SSO Microsoft + provisioning JIT
├── Jobs/
│   ├── SyncGraphCalendarJob.php                 # Dispatch N jobs individuels
│   ├── SyncGraphCalendarForRecipientJob.php     # Sync un destinataire (create/update/delete)
│   └── EnsureCalendarEntryJob.php               # Vérifie l'événement Outlook, met à jour ou recrée
├── Models/
│   ├── Event.php
│   ├── EventType.php                            # Pivot → DistributionList
│   ├── DistributionList.php                     # Pivot → EventType
│   ├── DistributionListRecipient.php
│   ├── GraphCalendarEntry.php                   # Suivi par destinataire (status, graph_event_id)
│   └── User.php
├── Observers/
│   └── EventObserver.php                        # Déclenche la sync sur created/updated/deleting
├── Services/
│   ├── MicrosoftGraphService.php                # Client Graph API (token OAuth2 + CRUD calendrier)
│   └── ActivityLogger.php                       # Journal d'activité
└── Support/
    └── SentryQueueReporting.php                 # Sentry : seul l'échec définitif d'un job est remonté

database/migrations/
└── 2026_01_01_000000_create_all_tables.php      # Migration unique
```

### Flux de synchronisation

```
Événement créé / modifié / supprimé
    └─► EventObserver
            └─► SyncGraphCalendarJob (dispatché en queue)
                    └─► collecte destinataires via eventType → distributionLists → recipients
                    └─► dispatch N × SyncGraphCalendarForRecipientJob
                                └─► MicrosoftGraphService (POST/PATCH/DELETE /users/{email}/events)
                                └─► GraphCalendarEntry (status: pending → synced | failed)
```

---

## Stack technique

| Composant | Version |
|---|---|
| Laravel | 13.x |
| PHP | 8.3+ |
| Vue 3 + Inertia.js | 3.x |
| Tailwind CSS | 4.x |
| Vite | 8.x |
| Base de données | SQLite |
| Queue | Database driver |
| Monitoring | Sentry |

---

## CI / CD

Workflows GitHub Actions (`.github/workflows/`) :

| Workflow | Déclencheur | Rôle |
|---|---|---|
| `ci.yml` | PR, push sur `main` | `composer install` et `npm run build` |
| `docker.yml` | push sur `main`, tags `v*` | Build et push de l'image vers ghcr.io |
| `dependabot-auto-merge.yml` | PR Dependabot | Auto-merge des mises à jour mineures et patch |

Dependabot (`.github/dependabot.yml`) surveille chaque lundi npm, Composer et GitHub Actions.

---

## Commandes utiles

```bash
# Promouvoir un utilisateur admin
php artisan event:make-admin prenom.nom@domaine.fr

# Vider le token Graph API mis en cache (après modification des permissions Azure)
php artisan cache:forget ms_graph_access_token

# Reset complet de la base
php artisan migrate:fresh --seed

# Build frontend
npm run build
npm run dev   # mode développement avec HMR

# Vider les caches Laravel
php artisan optimize:clear
```
