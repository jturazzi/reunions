<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Insère tous les événements du planning de l'association 2026–2027.
 * created_by = null (événements importés, pas de rédacteur SSO au moment du seed).
 */
class PlanningSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Vider les tables dépendantes avant les événements
        DB::table('graph_calendar_entries')->delete();
        DB::table('events')->delete();

        // Raccourci : récupère l'id d'un type par son nom
        $tid = fn (string $name): int => EventType::where('name', $name)->value('id');

        $events = [

            // ── Assemblée générale ────────────────────────────────────────────
            ['title' => 'Assemblée générale',             'type' => 'Assemblée générale',             'start' => '2027-04-13 18:00', 'end' => '2027-04-13 21:00'],

            // ── Conseil d'administration ──────────────────────────────────────
            ['title' => "Conseil d'administration 1",     'type' => "Conseil d'administration",       'start' => '2026-10-13 18:00', 'end' => '2026-10-13 22:00'],
            ['title' => "Conseil d'administration 2",     'type' => "Conseil d'administration",       'start' => '2027-01-12 18:00', 'end' => '2027-01-12 22:00'],
            ['title' => "Conseil d'administration 3",     'type' => "Conseil d'administration",       'start' => '2027-03-23 18:00', 'end' => '2027-03-23 22:00'],
            ['title' => "Séminaire Conseil d'administration", 'type' => "Conseil d'administration",   'start' => '2027-06-01 09:30', 'end' => '2027-06-01 17:00'],

            // ── Bureau (18h–22h) ──────────────────────────────────────────────
            ['title' => 'Bureau 1',                       'type' => 'Bureau',                         'start' => '2026-09-15 18:00', 'end' => '2026-09-15 22:00'],
            ['title' => 'Bureau 2',                       'type' => 'Bureau',                         'start' => '2026-11-17 18:00', 'end' => '2026-11-17 22:00'],
            ['title' => 'Bureau 3',                       'type' => 'Bureau',                         'start' => '2026-12-15 18:00', 'end' => '2026-12-15 22:00'],
            ['title' => 'Bureau 4',                       'type' => 'Bureau',                         'start' => '2027-02-09 18:00', 'end' => '2027-02-09 22:00'],
            ['title' => 'Bureau 5',                       'type' => 'Bureau',                         'start' => '2027-04-01 18:00', 'end' => '2027-04-01 22:00'],
            ['title' => 'Bureau 6',                       'type' => 'Bureau',                         'start' => '2027-05-13 18:00', 'end' => '2027-05-13 22:00'],
            ['title' => 'Bureau 7',                       'type' => 'Bureau',                         'start' => '2027-06-22 18:00', 'end' => '2027-06-22 22:00'],

            // ── Journée associative ───────────────────────────────────────────
            ['title' => 'Journée associative',            'type' => 'Journée associative',            'start' => '2026-09-17 09:00', 'end' => '2026-09-17 17:00'],

            // ── Erasmus Day ───────────────────────────────────────────────────
            ['title' => 'Erasmus Day : Journée laïcité',  'type' => 'Erasmus Day',                    'start' => '2026-10-15 09:00', 'end' => '2026-10-15 17:00'],

            // ── Rencontre conviviale et anciens ───────────────────────────────
            ['title' => 'Rencontre conviviale et anciens','type' => 'Rencontre conviviale et anciens','start' => '2027-06-26 10:00', 'end' => '2027-06-26 17:00'],

            // ── Vœux ─────────────────────────────────────────────────────────
            ['title' => 'Voeux Saint Etienne',            'type' => 'Voeux Saint Etienne',            'start' => '2027-01-08 12:00', 'end' => '2027-01-08 14:00'],
            ['title' => 'Voeux Roanne',                   'type' => 'Voeux Roanne',                   'start' => '2027-01-22 12:00', 'end' => '2027-01-22 14:00'],

            // ── CODIR (9h30–12h) ──────────────────────────────────────────────
            ['title' => 'CODIR 1',                        'type' => 'CODIR',                          'start' => '2026-06-04 09:30', 'end' => '2026-06-04 12:00'],
            ['title' => 'CODIR 2',                        'type' => 'CODIR',                          'start' => '2026-06-30 09:30', 'end' => '2026-06-30 12:00'],
            ['title' => 'CODIR 3',                        'type' => 'CODIR',                          'start' => '2026-09-08 09:30', 'end' => '2026-09-08 12:00'],
            ['title' => 'CODIR 4',                        'type' => 'CODIR',                          'start' => '2026-10-06 09:30', 'end' => '2026-10-06 12:00'],
            ['title' => 'CODIR 5',                        'type' => 'CODIR',                          'start' => '2026-11-10 09:30', 'end' => '2026-11-10 12:00'],
            ['title' => 'CODIR 6',                        'type' => 'CODIR',                          'start' => '2026-12-01 09:30', 'end' => '2026-12-01 12:00'],
            ['title' => 'CODIR 7',                        'type' => 'CODIR',                          'start' => '2027-01-05 09:30', 'end' => '2027-01-05 12:00'],
            ['title' => 'CODIR 8',                        'type' => 'CODIR',                          'start' => '2027-02-02 09:30', 'end' => '2027-02-02 12:00'],
            ['title' => 'CODIR 9',                        'type' => 'CODIR',                          'start' => '2027-03-16 09:30', 'end' => '2027-03-16 12:00'],
            ['title' => 'CODIR 10',                       'type' => 'CODIR',                          'start' => '2027-04-06 09:30', 'end' => '2027-04-06 12:00'],
            ['title' => 'CODIR 11',                       'type' => 'CODIR',                          'start' => '2027-05-04 09:30', 'end' => '2027-05-04 12:00'],
            ['title' => 'CODIR 12',                       'type' => 'CODIR',                          'start' => '2027-06-15 09:30', 'end' => '2027-06-15 12:00'],

            // ── CODIR élargi (9h30–12h) ───────────────────────────────────────
            ['title' => 'CODIR élargi 1',                 'type' => 'CODIR élargi',                   'start' => '2026-06-16 09:30', 'end' => '2026-06-16 12:00'],
            ['title' => 'CODIR élargi 2',                 'type' => 'CODIR élargi',                   'start' => '2026-09-22 09:30', 'end' => '2026-09-22 12:00'],
            ['title' => 'CODIR élargi 3',                 'type' => 'CODIR élargi',                   'start' => '2026-10-16 09:30', 'end' => '2026-10-16 12:00'],
            ['title' => 'CODIR élargi 4',                 'type' => 'CODIR élargi',                   'start' => '2026-11-24 09:30', 'end' => '2026-11-24 12:00'],
            ['title' => 'CODIR élargi 5',                 'type' => 'CODIR élargi',                   'start' => '2026-12-18 09:30', 'end' => '2026-12-18 12:00'],
            ['title' => 'CODIR élargi 6',                 'type' => 'CODIR élargi',                   'start' => '2027-01-19 09:30', 'end' => '2027-01-19 12:00'],
            ['title' => 'CODIR élargi 7',                 'type' => 'CODIR élargi',                   'start' => '2027-02-10 09:30', 'end' => '2027-02-10 12:00'],
            ['title' => 'CODIR élargi 8',                 'type' => 'CODIR élargi',                   'start' => '2027-03-26 09:30', 'end' => '2027-03-26 12:00'],
            ['title' => 'CODIR élargi 9',                 'type' => 'CODIR élargi',                   'start' => '2027-04-12 09:30', 'end' => '2027-04-12 12:00'],
            ['title' => 'CODIR élargi 10',                'type' => 'CODIR élargi',                   'start' => '2027-05-25 09:30', 'end' => '2027-05-25 12:00'],
            ['title' => 'CODIR élargi 11',                'type' => 'CODIR élargi',                   'start' => '2027-06-24 09:30', 'end' => '2027-06-24 12:00'],
            // Séminaires CODIR élargi (la veille 17h → lendemain 17h)
            ['title' => 'Séminaire CODIR élargi 1',       'type' => 'CODIR élargi',                   'start' => '2026-12-02 17:00', 'end' => '2026-12-03 17:00'],
            ['title' => 'Séminaire CODIR élargi 2',       'type' => 'CODIR élargi',                   'start' => '2027-05-10 17:00', 'end' => '2027-05-11 17:00'],

            // ── Coordination services support (14h–15h30) ─────────────────────
            ['title' => 'Coordination services support 1','type' => 'Coordination services support',  'start' => '2026-09-21 14:00', 'end' => '2026-09-21 15:30'],
            ['title' => 'Coordination services support 2','type' => 'Coordination services support',  'start' => '2026-10-12 14:00', 'end' => '2026-10-12 15:30'],
            ['title' => 'Coordination services support 3','type' => 'Coordination services support',  'start' => '2026-11-16 14:00', 'end' => '2026-11-16 15:30'],
            ['title' => 'Coordination services support 4','type' => 'Coordination services support',  'start' => '2026-12-14 14:00', 'end' => '2026-12-14 15:30'],
            ['title' => 'Coordination services support 5','type' => 'Coordination services support',  'start' => '2027-01-11 14:00', 'end' => '2027-01-11 15:30'],
            ['title' => 'Coordination services support 6','type' => 'Coordination services support',  'start' => '2027-02-08 14:00', 'end' => '2027-02-08 15:30'],
            ['title' => 'Coordination services support 7','type' => 'Coordination services support',  'start' => '2027-03-08 14:00', 'end' => '2027-03-08 15:30'],
            ['title' => 'Coordination services support 8','type' => 'Coordination services support',  'start' => '2027-04-05 14:00', 'end' => '2027-04-05 15:30'],
            ['title' => 'Coordination services support 9','type' => 'Coordination services support',  'start' => '2027-05-24 14:00', 'end' => '2027-05-24 15:30'],
            ['title' => 'Séminaire services support',     'type' => 'Coordination services support',  'start' => '2027-06-23 09:30', 'end' => '2027-06-23 17:00'],

            // ── Rencontre des chefs de service ────────────────────────────────
            ['title' => 'Rencontre des chefs de service 1','type' => 'Rencontre des chefs de service','start' => '2026-10-02 09:30', 'end' => '2026-10-02 14:00'],
            ['title' => 'Rencontre des chefs de service 2','type' => 'Rencontre des chefs de service','start' => '2026-12-07 09:30', 'end' => '2026-12-07 14:00'],
            ['title' => 'Rencontre des chefs de service 3','type' => 'Rencontre des chefs de service','start' => '2027-04-09 09:30', 'end' => '2027-04-09 14:00'],
            ['title' => 'Séminaire des chefs de service', 'type' => 'Rencontre des chefs de service', 'start' => '2027-06-28 09:30', 'end' => '2027-06-28 17:00'],

            // ── Rencontre transversale (12h–13h30) ────────────────────────────
            ['title' => 'Rencontre transversale 1',       'type' => 'Rencontre transversale',         'start' => '2026-09-21 12:00', 'end' => '2026-09-21 13:30'],
            ['title' => 'Rencontre transversale 2',       'type' => 'Rencontre transversale',         'start' => '2026-10-12 12:00', 'end' => '2026-10-12 13:30'],
            ['title' => 'Rencontre transversale 3',       'type' => 'Rencontre transversale',         'start' => '2026-11-16 12:00', 'end' => '2026-11-16 13:30'],
            ['title' => 'Rencontre transversale 4',       'type' => 'Rencontre transversale',         'start' => '2026-12-14 12:00', 'end' => '2026-12-14 13:30'],
            ['title' => 'Rencontre transversale 5',       'type' => 'Rencontre transversale',         'start' => '2027-01-11 12:00', 'end' => '2027-01-11 13:30'],
            ['title' => 'Rencontre transversale 6',       'type' => 'Rencontre transversale',         'start' => '2027-02-08 12:00', 'end' => '2027-02-08 13:30'],
            ['title' => 'Rencontre transversale 7',       'type' => 'Rencontre transversale',         'start' => '2027-03-08 12:00', 'end' => '2027-03-08 13:30'],
            ['title' => 'Rencontre transversale 8',       'type' => 'Rencontre transversale',         'start' => '2027-04-05 12:00', 'end' => '2027-04-05 13:30'],
            ['title' => 'Rencontre transversale 9',       'type' => 'Rencontre transversale',         'start' => '2027-05-24 12:00', 'end' => '2027-05-24 13:30'],
            ['title' => 'Rencontre transversale 10',      'type' => 'Rencontre transversale',         'start' => '2027-06-21 12:00', 'end' => '2027-06-21 13:30'],

            // ── Accueil des nouveaux salariés (9h–14h) ────────────────────────
            ['title' => 'Accueil des nouveaux salariés 1','type' => 'Accueil des nouveaux salariés',  'start' => '2026-11-19 09:00', 'end' => '2026-11-19 14:00'],
            ['title' => 'Accueil des nouveaux salariés 2','type' => 'Accueil des nouveaux salariés',  'start' => '2027-04-08 09:00', 'end' => '2027-04-08 14:00'],

            // ── Rencontres personnels administratifs (9h–14h) ─────────────────
            ['title' => 'Rencontres personnels administratifs 1','type' => 'Rencontres personnels administratifs','start' => '2026-06-26 09:00','end' => '2026-06-26 14:00'],
            ['title' => 'Rencontres personnels administratifs 2','type' => 'Rencontres personnels administratifs','start' => '2026-12-10 09:00','end' => '2026-12-10 14:00'],
            ['title' => 'Rencontres personnels administratifs 3','type' => 'Rencontres personnels administratifs','start' => '2027-06-29 09:00','end' => '2027-06-29 14:00'],

            // ── Comité de mécénat (15h–17h) ───────────────────────────────────
            ['title' => 'Comité de mécénat 1',            'type' => 'Comité de mécénat',              'start' => '2026-10-14 15:00', 'end' => '2026-10-14 17:00'],
            ['title' => 'Comité de mécénat 2',            'type' => 'Comité de mécénat',              'start' => '2027-02-03 15:00', 'end' => '2027-02-03 17:00'],
            ['title' => 'Comité de mécénat 3',            'type' => 'Comité de mécénat',              'start' => '2027-05-12 15:00', 'end' => '2027-05-12 17:00'],

            // ── Comité Charles Rebour (13h30–14h30) ───────────────────────────
            ['title' => 'Comité Charles Rebour 1',        'type' => 'Comité Charles Rebour',          'start' => '2026-10-14 13:30', 'end' => '2026-10-14 14:30'],
            ['title' => 'Comité Charles Rebour 2',        'type' => 'Comité Charles Rebour',          'start' => '2027-02-03 13:30', 'end' => '2027-02-03 14:30'],
            ['title' => 'Comité Charles Rebour 3',        'type' => 'Comité Charles Rebour',          'start' => '2027-05-12 13:30', 'end' => '2027-05-12 14:30'],

            // ── NAO (9h30–12h) ────────────────────────────────────────────────
            ['title' => 'NAO 1',                          'type' => 'NAO',                            'start' => '2026-10-14 09:30', 'end' => '2026-10-14 12:00'],
            ['title' => 'NAO 2',                          'type' => 'NAO',                            'start' => '2027-02-03 09:30', 'end' => '2027-02-03 12:00'],
            ['title' => 'NAO 3',                          'type' => 'NAO',                            'start' => '2027-05-12 09:30', 'end' => '2027-05-12 12:00'],

            // ── CSE ───────────────────────────────────────────────────────────
            ['title' => 'CSE 1',                          'type' => 'CSE',                            'start' => '2026-09-28 14:00', 'end' => '2026-09-28 17:00'],
            ['title' => 'CSE 2',                          'type' => 'CSE',                            'start' => '2026-11-06 09:00', 'end' => '2026-11-06 12:00'],
            ['title' => 'CSE 3',                          'type' => 'CSE',                            'start' => '2026-12-07 14:00', 'end' => '2026-12-07 17:00'],
            ['title' => 'CSE 4',                          'type' => 'CSE',                            'start' => '2027-01-18 14:00', 'end' => '2027-01-18 17:00'],
            ['title' => 'CSE 5',                          'type' => 'CSE',                            'start' => '2027-02-12 09:00', 'end' => '2027-02-12 12:00'],
            ['title' => 'CSE 6',                          'type' => 'CSE',                            'start' => '2027-03-22 14:00', 'end' => '2027-03-22 17:00'],
            ['title' => 'CSE 7',                          'type' => 'CSE',                            'start' => '2027-05-10 14:00', 'end' => '2027-05-10 17:00'],
            ['title' => 'CSE 8',                          'type' => 'CSE',                            'start' => '2027-06-25 09:00', 'end' => '2027-06-25 12:00'],
        ];

        foreach ($events as $e) {
            Event::create([
                'title'         => $e['title'],
                'start_at'      => $e['start'],
                'end_at'        => $e['end'],
                'event_type_id' => $tid($e['type']),
                'created_by'    => null,
            ]);
        }

        $this->command->info('✅ ' . count($events) . ' événements du planning insérés.');
    }
}
