<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenceDay extends Model
{
    protected $fillable = [
        'adherent_id',
        'date',
        'first_checkin_at',
        'source',
    ];

    protected $casts = [
        'date' => 'date',
        'first_checkin_at' => 'datetime',
    ];

    public function adherent(): BelongsTo
    {
        // Votre modèle s’appelle "Adherents"
        return $this->belongsTo(Adherents::class, 'adherent_id');
    }
}
