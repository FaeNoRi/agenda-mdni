<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salles extends Model
{
    protected $table = 'salles';
    public $timestamps = false;

    protected $fillable = ['nom_salle', 'type_salle'];

    /**
     * « Salles » qui n'en sont pas : la salle d'id 0 et « En extérieur » sont des emplacements fictifs
     * (comme « Non » pour les personnes) : plusieurs événements peuvent s'y tenir en même temps.
     */
    public function estFictive(): bool
    {
        return $this->id === 0 || self::nomFictif((string) $this->nom_salle);
    }

    public static function nomFictif(string $nom): bool
    {
        return in_array(mb_strtolower(trim($nom)), ['en extérieur', 'en exterieur'], true);
    }

    public function evenements()
    {
        return $this->belongsToMany(Evenements::class, 'evenement_salles', 'salle_id', 'evenement_id');
    }
}

