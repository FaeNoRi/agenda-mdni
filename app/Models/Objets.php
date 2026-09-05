<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Objets extends Model
{
    protected $table = 'objets';
    public $timestamps = false;

    protected $fillable = ['nom_obj'];

    public function evenements()
    {
        return $this->belongsToMany(Evenements::class, 'evenement_objets', 'objet_id', 'evenement_id');
    }
}

