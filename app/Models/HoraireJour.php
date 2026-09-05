<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HoraireJour extends Model
{
    use HasFactory;

    protected $fillable = [
        'horaire_id',
        'jour',
        'debut',
        'pause_debut',
        'pause_fin',
        'fin',
        'matin',
        'aprem',
        'repos',
    ];
    
    protected $casts = [
        'debut' => 'datetime:H:i',
        'pause_debut' => 'datetime:H:i',
        'pause_fin' => 'datetime:H:i',
        'fin' => 'datetime:H:i',
        'matin' => 'boolean',
        'aprem' => 'boolean',
        'repos' => 'boolean',
    ];

    public function horaire()
    {
        return $this->belongsTo(Horaire::class);
    }
}
