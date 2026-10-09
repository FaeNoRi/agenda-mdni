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

    /**
     * Pastille d'échéance : ['ton' => retard|calme|bientot|normal|neutre, 'texte' => ..., 'icone' => ...].
     * Rouge si en retard ; grise si terminé, annulé ou « À valider » (le travail est rendu) ;
     * ambre pour aujourd'hui et demain ; bleue sinon. $avecJours ajoute « · J-n » (cartes projet).
     */
    public function echeance(bool $avecJours = false, ?Carbon $le = null): array
    {
        $icone = $this instanceof \App\Models\Projet ? 'flag' : 'calendar';

        if (!$this->date_limite) {
            return ['ton' => 'neutre', 'texte' => 'Sans date', 'icone' => $icone];
        }

        if ($retard = $this->joursDeRetard($le)) {
            return ['ton' => 'retard', 'texte' => $retard.' j de retard', 'icone' => 'alert-triangle'];
        }

        $court = $this->date_limite->translatedFormat('j M');
        $statut = $this->statutActuel();

        if (!$statut->estOuvert() || $statut->value === 'a_valider') {
            return ['ton' => 'calme', 'texte' => $court, 'icone' => $icone];
        }

        $jours = (int) ($le ?? Carbon::today())->copy()->startOfDay()->diffInDays($this->date_limite->copy()->startOfDay());

        return match (true) {
            $jours === 0 => ['ton' => 'bientot', 'texte' => "Aujourd'hui", 'icone' => 'clock'],
            $jours === 1 => ['ton' => 'bientot', 'texte' => 'Demain', 'icone' => 'clock'],
            default => ['ton' => 'normal', 'texte' => $court.($avecJours ? ' · J-'.$jours : ''), 'icone' => $icone],
        };
    }
}
