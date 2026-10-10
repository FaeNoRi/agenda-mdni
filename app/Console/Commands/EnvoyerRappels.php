<?php

namespace App\Console\Commands;

use App\Services\RappelsPlanifies;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EnvoyerRappels extends Command
{
    protected $signature = 'notifications:rappels {--date= : Jour simulé (AAAA-MM-JJ), pour tester}';

    protected $description = 'Crée les rappels du jour (échéances, objets à remettre, photos, conflits) et purge les notifications lues anciennes';

    public function handle(RappelsPlanifies $rappels): int
    {
        $jour = $this->option('date') ? Carbon::parse($this->option('date')) : today();

        $n = $rappels->executer($jour);

        $this->info('Rappels du '.$jour->format('d/m/Y').' : '
            .$n['taches'].' tâche(s), '.$n['projets'].' projet(s), '.$n['evenements'].' événement(s) ; '
            .$n['supprimees'].' ancienne(s) notification(s) supprimée(s).');

        return self::SUCCESS;
    }
}
