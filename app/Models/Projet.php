<?php

namespace App\Models;

use App\Enums\ProjetEtat;
use App\Enums\TacheStatut;
use App\Models\Concerns\HasRetard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Projet extends Model
{
    use HasFactory, HasRetard;

    protected $fillable = ['nom', 'description', 'date_limite', 'etat', 'raison', 'created_by'];

    protected $casts = [
        'date_limite' => 'date',
        'etat' => ProjetEtat::class,
    ];

    protected $attributes = ['etat' => 'en_attente'];

    public function statutActuel(): ProjetEtat
    {
        return $this->etat;
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Toutes les personnes du projet (référents et impliqués), avec leur rôle dans le pivot. */
    public function membres(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'projet_user')->withPivot('role')->withTimestamps();
    }

    public function referents(): BelongsToMany
    {
        return $this->membres()->wherePivot('role', 'referent');
    }

    public function impliques(): BelongsToMany
    {
        return $this->membres()->wherePivot('role', 'implique');
    }

    public function taches(): HasMany
    {
        return $this->hasMany(Tache::class);
    }

    public function commentaires(): MorphMany
    {
        return $this->morphMany(Commentaire::class, 'commentable');
    }

    public function liens(): MorphMany
    {
        return $this->morphMany(Lien::class, 'lienable');
    }

    /** Tâches pas encore terminées ni annulées (sert à l'avertissement avant de clore un projet). */
    public function tachesOuvertes(): int
    {
        return $this->tachesCollection()->filter(fn (Tache $t) => $t->statut->estOuvert())->count();
    }

    /**
     * Résumé du projet : compteurs par statut et texte « 8 tâches — 5 terminées — 2 en cours — 1 bloquée ».
     *  - total   : toutes les tâches (annulées comprises, pour que le texte "tombe juste") ;
     *  - actives : hors annulées, base de l'anneau « terminées / total » ;
     *  - par_statut : [valeur du statut => nombre] (statuts à 0 omis).
     */
    public function resume(): array
    {
        $taches = $this->tachesCollection();
        $parStatut = $taches->countBy(fn (Tache $t) => $t->statut->value);
        $annulees = $parStatut[TacheStatut::Annule->value] ?? 0;
        $total = $taches->count();

        $morceaux = [];
        foreach (TacheStatut::ordreResume() as $statut) {
            if ($n = $parStatut[$statut->value] ?? 0) {
                $morceaux[] = $n.' '.$statut->accorde($n);
            }
        }

        $texte = $total === 0
            ? 'Aucune tâche'
            : $total.' '.($total > 1 ? 'tâches' : 'tâche').' — '.implode(' — ', $morceaux);

        return [
            'total' => $total,
            'actives' => $total - $annulees,
            'terminees' => $parStatut[TacheStatut::Termine->value] ?? 0,
            'en_retard' => $taches->filter(fn (Tache $t) => $t->estEnRetard())->count(),
            'par_statut' => $parStatut->all(),
            'texte' => $texte,
        ];
    }

    private function tachesCollection()
    {
        return $this->relationLoaded('taches') ? $this->taches : $this->taches()->get();
    }
}
