<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Série de tâches récurrentes (hebdomadaire ou mensuelle) jusqu'à une date de fin.
 * Une seule occurrence est ouverte à la fois : la suivante naît quand la précédente se termine
 * (ou est annulée « cette occurrence seulement »). Annuler « toutes » arrête la série.
 */
class Recurrence extends Model
{
    public const HEBDOMADAIRE = 'hebdomadaire';
    public const MENSUELLE = 'mensuelle';

    protected $fillable = ['frequence', 'date_fin', 'arretee', 'created_by'];

    protected $casts = ['date_fin' => 'date', 'arretee' => 'boolean'];

    public function taches(): HasMany
    {
        return $this->hasMany(Tache::class);
    }

    public function libelle(): string
    {
        return $this->frequence === self::MENSUELLE ? 'Chaque mois' : 'Chaque semaine';
    }

    /**
     * Crée l'occurrence qui suit $precedente (mêmes titre, détails, projet, responsables et liens),
     * ou renvoie null : série arrêtée, date de fin dépassée, ou occurrence suivante déjà là.
     */
    public function genererSuivante(Tache $precedente): ?Tache
    {
        if ($this->arretee) {
            return null;
        }

        $existantes = $this->taches()->orderBy('date_limite')->get();

        // Déjà une occurrence plus tardive (tâche rouverte puis refermée) : rien à créer.
        if ($existantes->contains(fn (Tache $t) => $t->date_limite->gt($precedente->date_limite))) {
            return null;
        }

        // Dates calculées depuis la première, pour ne pas dériver (31 janv. → 28 févr. → 31 mars).
        $debut = $existantes->first()->date_limite->copy();
        $rang = $existantes->count();
        $prochaine = $this->frequence === self::MENSUELLE
            ? $debut->addMonthsNoOverflow($rang)
            : $debut->addWeeks($rang);

        if ($prochaine->gt($this->date_fin)) {
            return null;
        }

        $suivante = Tache::create([
            'projet_id' => $precedente->projet_id,
            'titre' => $precedente->titre,
            'details' => $precedente->details,
            'date_limite' => $prochaine,
            'statut' => 'a_faire',
            'recurrence_id' => $this->id,
            'created_by' => $precedente->created_by,
        ]);

        $suivante->responsables()->sync($precedente->responsables()->pluck('users.id')->all());

        foreach ($precedente->liens as $lien) {
            $suivante->liens()->create(['libelle' => $lien->libelle, 'url' => $lien->url]);
        }

        return $suivante;
    }
}
