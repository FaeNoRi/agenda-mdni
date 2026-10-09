<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * Retard d'un projet ou d'une tâche : date limite dépassée et statut ni « Terminé » ni « Annulé ».
 * « À valider », « En attente » et « Bloqué » restent donc en retard (le travail rendu n'est pas
 * techniquement terminé à temps).
 * Le modèle doit fournir `date_limite` (date) et `statutActuel()`.
 */
trait HasRetard
{
    abstract public function statutActuel(): \BackedEnum;

    public function estEnRetard(?Carbon $le = null): bool
    {
        return $this->joursDeRetard($le) > 0;
    }

    public function joursDeRetard(?Carbon $le = null): int
    {
        if (!$this->date_limite || !$this->statutActuel()->estOuvert()) {
            return 0;
        }

        $aujourdhui = ($le ?? Carbon::today())->copy()->startOfDay();
        $limite = $this->date_limite->copy()->startOfDay();

        return $limite->lt($aujourdhui) ? (int) $limite->diffInDays($aujourdhui) : 0;
    }
}
