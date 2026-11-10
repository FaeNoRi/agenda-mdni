<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conge extends Model
{
    protected $table = 'conges';
    protected $fillable = ['user_id','start','end'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Journée(s) entière(s) : début ET fin à 00:00 (ex. 17/09 00:00 → 18/09 00:00 = les 17 et 18). */
    public function estJourneeEntiere(): bool
    {
        return Carbon::parse($this->start)->format('H:i:s') === '00:00:00'
            && Carbon::parse($this->end)->format('H:i:s') === '00:00:00';
    }

    public function debutEffectif(): Carbon
    {
        return Carbon::parse($this->start);
    }

    /**
     * Fin réelle (exclusive) du congé : pour un congé en journées entières, la date de fin
     * est incluse, donc le congé court jusqu'au lendemain 00:00.
     */
    public function finEffective(): Carbon
    {
        $fin = Carbon::parse($this->end);

        return $this->estJourneeEntiere() ? $fin->addDay() : $fin;
    }

    /** Le congé chevauche-t-il réellement le créneau [$debut, $fin[ ? */
    public function chevauche(CarbonInterface $debut, CarbonInterface $fin): bool
    {
        return $this->debutEffectif()->lt($fin) && $this->finEffective()->gt($debut);
    }

    /**
     * Congés susceptibles de chevaucher le créneau (pré-filtre SQL élargi d'un jour pour la
     * fin incluse des journées entières), à affiner avec chevauche().
     */
    public function scopeCandidatsPour(Builder $query, CarbonInterface $debut, CarbonInterface $fin): Builder
    {
        return $query->where('start', '<', $fin)->where('end', '>=', $debut->copy()->subDay());
    }
}
