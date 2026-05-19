# Réunions — Gestion des événements associatifs

Application interne **Laravel 13** pour la planification et la diffusion des événements de l'ANEF Loire.

Les membres reçoivent une **invitation calendrier ICS** par email SMTP (Outlook affiche les boutons Accepter / Refuser / Tentative). L'authentification est déléguée à **Microsoft 365 SSO**.

---

## Fonctionnalités

### Événements
- Création, modification, suppression d'événements (titre, dates, lieu, salle de réunion, description)
- Classement par **type d'événement** (catégorie)
- Envoi automatique des invitations `.ics` à la création
- Mise à jour automatique (`SEQUENCE` incrémentée) si les données calendrier changent
- Annulation automatique (`METHOD:CANCEL`) à la suppression
- Journal de suivi des envois (statut, date, erreurs) par événement

### Types d'événements
- Catégories réutilisables (Conseil d'administration, Assemblée générale…)
- **Listes de distribution assignées par type** : toute liste liée à un type reçoit les invitations de tous ses événements

### Listes de distribution
- Listes nommées et réutilisables de destinataires
- Assignables à un ou plusieurs types d'événements
- Ajout d'un destinataire → invitation automatique pour les événements futurs déjà planifiés
- Suppression d'un destinataire → annulation automatique envoyée à Outlook

### Envoi des invitations
- Chaque email est un job de queue indépendant avec **délai configurable** entre envois (`MAIL_SEND_DELAY_SECONDS`)
- Retry automatique en cas d'échec SMTP (3 tentatives, backoff exponentiel)
- Renvoi manuel depuis la page de détail d'un événement

---

## Prérequis

- PHP **8.3+**, Composer
- Node.js 20+
- Serveur SMTP (Exchange Online, Postfix…)
- Tenant Microsoft 365 / Entra ID (pour le SSO uniquement)

---

## Installation

```bash
git clone <repo> && cd reunions
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

# Queue (base de données recommandée, Redis possible)
QUEUE_CONNECTION=database

# SMTP sortant
MAIL_MAILER=smtp
MAIL_HOST=votre-relay.mail.protection.outlook.com
MAIL_PORT=25
MAIL_FROM_ADDRESS=reunions@votre-domaine.fr
MAIL_FROM_NAME="Réunions"

# Délai entre chaque email (secondes) — évite le rate-limiting SMTP
MAIL_SEND_DELAY_SECONDS=3

# SSO Microsoft 365 (connexion des utilisateurs uniquement)
MICROSOFT_CLIENT_ID=xxxx
MICROSOFT_CLIENT_SECRET=xxxx
MICROSOFT_TENANT_ID=xxxx
MICROSOFT_REDIRECT_URI=https://votre-domaine.fr/auth/microsoft/callback
```

---

## Configuration Azure (Entra ID)

### 1. Créer l'App Registration

1. **Azure Portal** → Entra ID → App registrations → New registration
2. Redirect URI : `https://votre-domaine.fr/auth/microsoft/callback` (type Web)

### 2. Permissions requises (Delegated uniquement — SSO)

| Permission | Type | Usage |
|---|---|---|
| `User.Read` | Delegated | Profil de l'utilisateur connecté |
| `openid`, `profile`, `email` | Delegated | Claims OIDC |

> Aucune permission Application (Graph API) n'est requise. Les invitations sont envoyées par SMTP.

### 3. Client Secret

Entra ID → App Registration → **Certificates & secrets** → New client secret → copier dans `MICROSOFT_CLIENT_SECRET`.

---

## Worker de queue

Les emails sont envoyés via la queue Laravel. Lancer le worker :

```bash
php artisan queue:work --sleep=3 --tries=3
```

En production, superviser avec **Supervisor** :

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

## Architecture

```
app/
├── Dto/
│   └── RecipientData.php          # VO léger (name + email) pour les Mailables
├── Http/Controllers/Admin/
│   ├── EventController.php        # CRUD événements + journal + renvoi
│   ├── EventTypeController.php    # CRUD types + assignation des listes
│   ├── DistributionListController.php  # CRUD listes + destinataires
│   └── LogController.php          # Journal d'activité
├── Jobs/
│   ├── SendEventInvitationsJob.php       # Dispatch N jobs individuels (création/MAJ/annulation)
│   ├── SendSingleInvitationJob.php       # Envoie UN email + log (avec délai et retry)
│   ├── SendNewRecipientInvitationsJob.php # Nouveau destinataire → événements futurs
│   └── SendRecipientCancellationJob.php  # Destinataire supprimé → CANCEL à Outlook
├── Mail/
│   └── EventInvitationMail.php    # Génère le .ics via spatie/icalendar-generator
├── Models/
│   ├── Event.php                  # ics_uid stable, ics_sequence auto-incrémenté
│   ├── EventType.php              # Pivot → DistributionList
│   ├── DistributionList.php       # Pivot → EventType
│   ├── DistributionListRecipient.php
│   └── EventInvitationLog.php     # Journal des envois
└── Observers/
    └── EventObserver.php          # Déclenche les jobs sur created/updated/deleting
```

### Flux d'une invitation

```
Événement créé
    └─► EventObserver::created()
            └─► SendEventInvitationsJob (dispatché)
                    └─► collecte destinataires via eventType → distributionLists → recipients
                    └─► dispatch N × SendSingleInvitationJob (délai : 0s, 3s, 6s…)
                                └─► EventInvitationMail (.ics METHOD:REQUEST)
                                └─► EventInvitationLog (status: sent | failed)
```

---

## Stack technique

| Composant | Version |
|---|---|
| Laravel | 13.x |
| PHP | 8.3+ |
| Vue 3 + Inertia.js | — |
| spatie/icalendar-generator | ^3.3 |
| socialiteproviders/microsoft | ^4.9 |
| Base de données | SQLite (configurable) |
| Queue | Database driver |

---

## Commandes utiles

```bash
# Migrations
php artisan migrate
php artisan migrate:status

# Vider les caches
php artisan optimize:clear

# Build frontend
npm run build
npm run dev          # mode développement avec HMR

# Logs
tail -f storage/logs/laravel.log
```

| `User.Read` | **Delegated** | Connexion SSO des utilisateurs |
| `Calendars.ReadWrite` | **Application** | Injection directe dans les agendas |

> ⚠️ La permission `Calendars.ReadWrite` de type **Application** nécessite un **Grant admin consent**.

### 3. Accorder le consentement admin

Azure Portal → App registration → **API permissions** → **Grant admin consent for [tenant]**

## Premier administrateur

```bash
# Après le premier login SSO du futur admin :
php artisan event:make-admin prenom.nom@votre-association.fr
```

## Flux de synchronisation Outlook

| Action admin | Effet sur les agendas Outlook des abonnés |
|---|---|
| Créer un événement | Événement ajouté directement dans l'agenda |
| Modifier un événement | Événement mis à jour dans l'agenda |
| Supprimer un événement | Événement retiré de l'agenda |
| Abonnement utilisateur | Tous les événements futurs du type ajoutés à l'agenda |
| Désabonnement utilisateur | Tous les événements futurs du type retirés de l'agenda |

## Détection automatique des comptes désactivés

Si un compte Microsoft 365 est désactivé ou supprimé dans Entra ID, Graph API retourne une erreur spécifique.  
L'application marque alors automatiquement l'utilisateur `is_active = false` et l'empêche de se connecter.

## Architecture clé

```
app/
├── Services/MicrosoftGraphService.php  # Client Graph API (token, CRUD calendrier)
├── Observers/EventObserver.php         # Synchronisation auto sur CRUD événement
├── Http/Controllers/Auth/              # SSO Microsoft + JIT Provisioning
├── Http/Controllers/Admin/             # CRUD événements et types (admin)
├── Http/Controllers/SubscriptionController.php  # Abonnements utilisateur
└── Console/Commands/MakeAdminCommand.php

database/migrations/
└── 2026_01_01_000000_create_all_tables.php  # Migration unique
```

## Commandes utiles

```bash
# Vider le token Graph API mis en cache (après modification des permissions Azure)
php artisan cache:forget ms_graph_token

# Promouvoir un utilisateur en administrateur
php artisan event:make-admin prenom.nom@domaine.fr

# Recréer la base de données
php artisan migrate:fresh --seed
```
