<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Horaire;
use App\Models\HoraireJour;

class SeedFakeHoraires extends Command
{
    protected $signature = 'horaires:seed-fake';
    protected $description = 'Génère 15 horaires fictifs et les lie aux utilisateurs.';

    public function handle(): void
    {
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $patterns = [
            ['08:00', '12:00', '13:30', '17:30'],
            ['Repos', 'Repos', 'Repos', 'Repos'],
            ['09:00', '12:30', '14:00', '18:00'],
        ];

        $this->info('🧹 Nettoyage des horaires existants...');
        Horaire::each(function ($horaire) {
            $horaire->jours()->delete();
            $horaire->delete();
        });
        // Reset auto-incrément
        DB::statement('ALTER TABLE horaire_jours AUTO_INCREMENT = 1;');
        DB::statement('ALTER TABLE horaires AUTO_INCREMENT = 1;');

        $users = User::all();
        $this->info("👥 {$users->count()} utilisateurs détectés.");

        foreach ($users as $index => $user) {
            $horaire = Horaire::create([
                'nom' => 'Planning_' . str_replace(' ', '_', $user->name),
            ]);

            foreach ($jours as $j => $jour) {
                $p = $patterns[($index + $j) % count($patterns)];

                $data = [
                    'horaire_id' => $horaire->id,
                    'jour' => $jour,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($p[0] === 'Repos') {
                    $data += [
                        'debut' => null,
                        'pause_debut' => null,
                        'pause_fin' => null,
                        'fin' => null,
                        'matin' => false,
                        'aprem' => false,
                        'repos' => true,
                    ];
                } else {
                    $data += [
                        'debut' => $p[0],
                        'pause_debut' => $p[1],
                        'pause_fin' => $p[2],
                        'fin' => $p[3],
                        'matin' => true,
                        'aprem' => true,
                        'repos' => false,
                    ];
                }

                HoraireJour::create($data);
            }

            $user->id_horaire = $horaire->id;
            $user->save();
        }

        $this->info('🎉 Tous les utilisateurs ont maintenant leur planning personnel !');
    }
}
