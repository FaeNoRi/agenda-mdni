<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Série de tâches récurrentes (hebdomadaire ou mensuelle) jusqu'à une date de fin. */
class Recurrence extends Model
{
    public const HEBDOMADAIRE = 'hebdomadaire';
    public const MENSUELLE = 'mensuelle';

    protected $fillable = ['frequence', 'date_fin', 'created_by'];

    protected $casts = ['date_fin' => 'date'];

    public function taches(): HasMany
    {
        return $this->hasMany(Tache::class);
    }
}
