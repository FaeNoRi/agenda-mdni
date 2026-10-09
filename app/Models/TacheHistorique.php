<?php

namespace App\Models;

use App\Enums\TacheStatut;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une ligne par changement de statut d'une tâche (créée automatiquement). */
class TacheHistorique extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'tache_historiques';

    protected $fillable = ['tache_id', 'user_id', 'statut', 'raison'];

    protected $casts = ['statut' => TacheStatut::class];

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
