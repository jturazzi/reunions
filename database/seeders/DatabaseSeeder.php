<?php

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Initialise les données par défaut :
     * - Types d'événements (planning de l'association)
     * - Tous les événements du planning (via PlanningSeeder)
     *
     * Le premier utilisateur à se connecter via SSO devient automatiquement admin.
     */
    public function run(): void
    {
        // Types d'événements issus du planning de l'association
        $eventTypes = [
            // Gouvernance
            ['name' => 'CODIR',                                'slug' => 'codir'],
            ['name' => 'CODIR élargi',                         'slug' => 'codir-elargi'],
            ['name' => 'Rencontres personnels administratifs',  'slug' => 'rencontres-personnels-administratifs'],

            // Réunions opérationnelles / RH
            ['name' => 'Coordination services support',        'slug' => 'coordination-services-support'],
            ['name' => 'Rencontre des chefs de service',        'slug' => 'rencontre-des-chefs-de-service'],
            ['name' => 'Accueil des nouveaux salariés',         'slug' => 'accueil-des-nouveaux-salaries'],
            ['name' => 'CSE',                                   'slug' => 'cse'],
            ['name' => 'Comité Charles Rebour',                 'slug' => 'comite-charles-rebour'],
            ['name' => 'Rencontre transversale',                'slug' => 'rencontre-transversale'],
            ['name' => 'Journée associative',                   'slug' => 'journee-associative'],
            ['name' => 'Assemblée générale',                    'slug' => 'assemblee-generale'],
            ['name' => 'Rencontre conviviale et anciens',       'slug' => 'rencontre-conviviale-et-anciens'],

            // Institutionnel / négociations
            ['name' => 'Voeux Saint Etienne',                  'slug' => 'voeux-saint-etienne'],
            ['name' => 'Voeux Roanne',                          'slug' => 'voeux-roanne'],
            ['name' => 'NAO',                                   'slug' => 'nao'],
            ['name' => 'Comité de mécénat',                     'slug' => 'comite-de-mecenat'],
            ['name' => 'Rencontres budgétaires',                'slug' => 'rencontres-budgetaires'],

            // Voyage / international
            ['name' => 'Voyage apprenant',                     'slug' => 'voyage-apprenant'],
            ['name' => 'Erasmus Day',                           'slug' => 'erasmus-day'],

            // Gouvernance statutaire
            ['name' => 'Bureau',                               'slug' => 'bureau'],
            ['name' => 'Conseil d\'administration',             'slug' => 'conseil-d-administration'],
        ];

        foreach ($eventTypes as $type) {
            EventType::firstOrCreate(['slug' => $type['slug']], $type);
        }

        $this->call(PlanningSeeder::class);

        $this->command->info('✅ Types d\'événements et planning créés.');
        $this->command->info('ℹ️  Le premier utilisateur à se connecter via SSO sera automatiquement administrateur.');
    }
}

