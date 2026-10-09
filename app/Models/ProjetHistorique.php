<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une ligne du journal d'un projet (changement du projet ou d'une de ses tâches). */
class ProjetHistorique extends Model
{
    public const UPDATED_AT = null;

    public const PROJET = 'projet';
    public const TACHE = 'tache';

    protected $table = 'projet_historiques';

    protected $fillable = ['projet_id', 'user_id', 'tache_id', 'type', 'libelle'];

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Ajoute une ligne au journal ; sans projet (tâche simple), ne fait rien. */
    public static function noter(?int $projetId, string $type, string $libelle, ?User $par = null, ?int $tacheId = null): void
    {
        if (!$projetId) {
            return;
        }

        static::create([
            'projet_id' => $projetId,
            'user_id' => $par?->id ?? auth()->id(),
            'tache_id' => $tacheId,
            'type' => $type,
            'libelle' => mb_strimwidth($libelle, 0, 500, '…'),
        ]);
    }
}
