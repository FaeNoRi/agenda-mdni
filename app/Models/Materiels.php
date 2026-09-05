<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materiels extends Model
{
    protected $table = 'materiels';
    public $timestamps = false;

    protected $fillable = ['nom_mat', 'nb_stock'];

    public function evenements()
    {
        return $this->belongsToMany(Evenements::class, 'evenement_materiels', 'materiel_id', 'evenement_id')
                    ->withPivot('quantite')
                    ->withTimestamps();
    }

}

