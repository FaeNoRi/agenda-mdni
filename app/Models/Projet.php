<?php

namespace App\Models;

use App\Enums\ProjetEtat;
use App\Enums\TacheStatut;
use App\Models\Concerns\HasRetard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;

class Projet extends Model
{
    use HasFactory, HasRetard;

    protected $fillable = ['nom', 'description', 'couleur', 'icone', 'date_limite', 'etat', 'raison', 'created_by'];

    protected $casts = [
        'date_limite' => 'date',
        'etat' => ProjetEtat::class,
    ];

    protected $attributes = ['etat' => 'en_attente', 'couleur' => '#4263eb', 'icone' => 'folder'];

    public function statutActuel(): ProjetEtat
    {
        return $this->etat;
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Personnes rattachées explicitement au projet : ses référents (rôle « referent » dans le pivot).
     * Les personnes « impliquées » ne sont pas saisies : elles se déduisent des tâches
     * (voir personnesImpliquees()).
     */
    public function membres(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'projet_user')->withPivot('role')->withTimestamps();
    }

    public function referents(): BelongsToMany
    {
        return $this->membres()->wherePivot('role', 'referent');
    }

    /**
     * Personnes impliquées dans le projet : ses référents, puis les responsables de ses tâches
     * (hors tâches annulées), sans doublon. Utilise les relations déjà chargées quand il y en a.
     */
    public function personnesImpliquees(): Collection
    {
        $this->loadMissing(['membres', 'taches.responsables']);

        $referents = $this->membres->filter(fn ($u) => $u->pivot->role === 'referent');
        $responsables = $this->taches
            ->filter(fn (Tache $t) => $t->statut !== TacheStatut::Annule)
            ->flatMap(fn (Tache $t) => $t->responsables);

        return $referents->merge($responsables)->unique('id')->values();
    }

    /** Impliqués qui ne sont pas référents (les référents sont affichés à part). */
    public function impliquesHorsReferents(): Collection
    {
        $referents = $this->membres->filter(fn ($u) => $u->pivot->role === 'referent')->pluck('id');

        return $this->personnesImpliquees()->reject(fn ($u) => $referents->contains($u->id))->values();
    }

    public function taches(): HasMany
    {
        return $this->hasMany(Tache::class);
    }

    public function commentaires(): MorphMany
    {
        return $this->morphMany(Commentaire::class, 'commentable')->orderBy('created_at')->orderBy('id');
    }

    public function liens(): MorphMany
    {
        return $this->morphMany(Lien::class, 'lienable');
    }

    public function estReferent(User $user): bool
    {
        return $this->referents()->where('users.id', $user->id)->exists();
    }

    /** Référent du projet, ou responsable d'une de ses tâches (hors tâches annulées). */
    public function estImplique(User $user): bool
    {
        return $this->estReferent($user)
            || $this->taches()
                ->where('statut', '!=', TacheStatut::Annule->value)
                ->whereHas('responsables', fn ($q) => $q->where('users.id', $user->id))
                ->exists();
    }

    /** Tâches pas encore terminées ni annulées (sert à l'avertissement avant de clore un projet). */
    public function tachesOuvertes(): int
    {
        return $this->tachesCollection()->filter(fn (Tache $t) => $t->statut->estOuvert())->count();
    }

    /** « 2 tâches ne sont pas terminées » / « 1 tâche n'est pas terminée ». */
    public function phraseTachesOuvertes(): string
    {
        $n = $this->tachesOuvertes();

        return $n.' '.($n > 1 ? 'tâches ne sont pas terminées' : "tâche n'est pas terminée");
    }

    /**
     * Change l'état du projet (saisi à la main par le référent) en appliquant la règle :
     *  - « Bloqué » et « En attente » exigent une raison ;
     *  - « Terminé » est refusé tant qu'il reste des tâches ouvertes.
     * Les autres incohérences ne bloquent pas : voir avertissements().
     * Retourne false si l'état est déjà celui demandé.
     */
    public function changerEtat(ProjetEtat $nouveau, ?string $raison = null): bool
    {
        $raison = $raison !== null ? trim($raison) : null;

        if ($nouveau->exigeRaison() && ($raison === null || $raison === '')) {
            throw ValidationException::withMessages([
                'raison' => "Précisez la raison pour l'état « ".$nouveau->label().' ».',
            ]);
        }

        if ($nouveau === ProjetEtat::Termine && $this->tachesOuvertes() > 0) {
            throw ValidationException::withMessages([
                'etat' => $this->phraseTachesOuvertes().' : terminez-les ou annulez-les avant de clore le projet.',
            ]);
        }

        if ($this->etat === $nouveau) {
            return false;
        }

        $this->forceFill(['etat' => $nouveau, 'raison' => $nouveau->exigeRaison() ? $raison : null])->save();

        return true;
    }

    /**
     * Incohérences entre l'état saisi et l'avancement des tâches (information, jamais bloquant).
     *
     * @return list<string>
     */
    public function avertissements(): array
    {
        $ouvertes = $this->tachesOuvertes();
        $actives = $this->resume()['actives'];
        $avertissements = [];

        switch ($this->etat) {
            case ProjetEtat::AValider:
                if ($ouvertes > 0) {
                    $avertissements[] = $this->phraseTachesOuvertes().' alors que le projet est à valider.';
                }
                break;
            case ProjetEtat::EnCours:
            case ProjetEtat::EnAttente:
            case ProjetEtat::Bloque:
                if ($actives > 0 && $ouvertes === 0) {
                    $avertissements[] = 'Toutes les tâches sont terminées : le projet peut passer à « À valider ».';
                }
                break;
            case ProjetEtat::Termine:
            case ProjetEtat::Annule:
                if ($ouvertes > 0) {
                    $avertissements[] = $this->phraseTachesOuvertes().' dans un projet '.mb_strtolower($this->etat->label()).'.';
                }
                break;
        }

        return $avertissements;
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
