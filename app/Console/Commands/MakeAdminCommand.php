<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Commande artisan pour promouvoir un utilisateur au rôle administrateur.
 *
 * Usage : php artisan event:make-admin utilisateur@domaine.fr
 */
class MakeAdminCommand extends Command
{
    protected $signature   = 'event:make-admin {email : Email de l\'utilisateur à promouvoir}';
    protected $description = 'Attribue le rôle administrateur à un utilisateur par son email';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user  = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Aucun utilisateur trouvé avec l'email : {$email}");
            $this->info('Assurez-vous que l\'utilisateur s\'est connecté au moins une fois via SSO.');
            return self::FAILURE;
        }

        if ($user->is_admin) {
            $this->warn("{$user->name} ({$email}) est déjà administrateur.");
            return self::SUCCESS;
        }

        $user->update(['is_admin' => true]);
        $this->info("✅ {$user->name} ({$email}) est maintenant administrateur.");

        return self::SUCCESS;
    }
}
