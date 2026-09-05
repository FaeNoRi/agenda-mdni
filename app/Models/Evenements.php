<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evenements extends Model
{
    use HasFactory;

    protected $attributes = [
        'desc_event' => '...',
    ];

    protected $casts = [
        'date_heure_debut' => 'datetime',
        'date_heure_fin'   => 'datetime',
    ];

    protected $fillable = [
        'nom_event',
        'type_event',
        'type_public',
        'desc_event',
        'commanditaire_event',
        'nbpart',
        'facture',
        'numfact',
        'devis',
        'numdevis',
        'reglement',
        'type_reglement',
        'num_reglement',
        'objet',
        'auteur',
        'date_heure_debut',
        'date_heure_fin',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'evenement_users', 'evenement_id', 'user_id');
    }

    public function salles()
    {
        return $this->belongsToMany(Salles::class, 'evenement_salles', 'evenement_id', 'salle_id');
    }

    public function materiels()
    {
        return $this->belongsToMany(Materiels::class, 'evenement_materiels', 'evenement_id', 'materiel_id')
                    ->withPivot('quantite')
                    ->withTimestamps();
    }

    public function objets()
    {
        return $this->belongsToMany(Objets::class, 'evenement_objets', 'evenement_id', 'objet_id')
                    ->withPivot('etat')
                    ->withTimestamps();
    }

}
