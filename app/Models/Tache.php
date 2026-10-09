<?php

namespace App\Models;

use App\Enums\TacheStatut;
use App\Models\Concerns\HasRetard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Tache extends Model
{
    use HasFactory, HasRetard;

    protected $table = 'taches';

    protected $fillable = ['projet_id', 'titre', 'details', 'date_limite', 'statut', 'raison', 'recurrence_id', 'created_by'];

    protected $casts = [
        'date_limite' => 'date',
        'statut' => TacheStatut::class,
    ];

    protected $attributes = ['statut' => 'a_faire'];

    /** L'état de départ est toujours la première ligne de l'historique. */
    protected static function booted(): void
    {
        static::created(function (Tache $tache) {
            $tache->historiques()->create([
                'user_id' => $tache->created_by,
                'statut' => $tache->statut,
                'raison' => $tache->raison,
            ]);
        });
    }

    public function statutActuel(): TacheStatut
    {
        return $this->statut;
    }

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(Recurrence::class);
    }

    public function responsables(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tache_user')->withTimestamps();
    }

    public function historiques(): HasMany
    {
        return $this->hasMany(TacheHistorique::class)->orderBy('created_at')->orderBy('id');
    }

    public function commentaires(): MorphMany
    {
        return $this->morphMany(Commentaire::class, 'commentable');
    }

    public function liens(): MorphMany
    {
        return $this->morphMany(Lien::class, 'lienable');
    }

    /** Tâche simple : rattachée à aucun projet. */
    public function estSimple(): bool
    {
        return $this->projet_id === null;
    }

    /**
     * Référents de la tâche, qui la valident et sont notifiés de son avancement :
     * les référents du projet, ou le créateur pour une tâche simple (ou un projet sans référent).
     */
    public function referents(): Collection
    {
        $referents = $this->projet ? $this->projet->referents : new Collection();

        // Tâche simple, ou projet sans référent : le créateur reste l'interlocuteur.
        return $referents->isNotEmpty() ? $referents : new Collection(array_filter([$this->createur]));
    }

    public function estReferent(User $user): bool
    {
        return $this->referents()->contains('id', $user->id);
    }

    /** Responsable de la tâche ou référent : la personne est « impliquée » dans la tâche. */
    public function estImplique(User $user): bool
    {
        return $this->personnesImpliquees()->contains('id', $user->id);
    }

    /** Toutes les personnes concernées : responsables + référents (sans doublon). */
    public function personnesImpliquees(): Collection
    {
        return $this->responsables->merge($this->referents())->unique('id')->values();
    }

    /**
     * Change le statut et l'inscrit dans l'historique. « En attente » et « Bloqué » exigent une raison.
     * Retourne false si le statut est déjà celui demandé (rien n'est écrit).
     */
    public function changerStatut(TacheStatut $nouveau, ?User $par = null, ?string $raison = null): bool
    {
        $raison = $raison !== null ? trim($raison) : null;

        if ($nouveau->exigeRaison() && ($raison === null || $raison === '')) {
            throw ValidationException::withMessages([
                'raison' => 'Précisez la raison pour le statut « '.$nouveau->label().' ».',
            ]);
        }

        if ($this->statut === $nouveau) {
            return false;
        }

        $garder = $nouveau->exigeRaison() ? $raison : null;

        DB::transaction(function () use ($nouveau, $par, $garder) {
            $this->forceFill(['statut' => $nouveau, 'raison' => $garder])->save();

            $this->historiques()->create([
                'user_id' => $par?->id,
                'statut' => $nouveau,
                'raison' => $garder,
            ]);
        });

        return true;
    }
}
