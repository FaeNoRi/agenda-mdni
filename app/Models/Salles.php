<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salles extends Model
{
    protected $table = 'salles';
    public $timestamps = false;

    protected $fillable = ['nom_salle', 'type_salle'];

    public function evenements()
    {
        return $this->belongsToMany(Evenements::class, 'evenement_salles', 'salle_id', 'evenement_id');
    }
}

