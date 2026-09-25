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

    /**
     * Présence d'un utilisateur sur cet événement :
     *  - 'named' : la personne est citée nommément (prioritaire) ;
     *  - 'team'  : seule "Toute l'équipe" (id 0) est citée, et la personne fait partie de l'équipe ;
     *  - null    : pas de mise en avant (non concernée, ou événement annulé).
     * Nécessite la relation users chargée pour éviter une requête par événement.
     */
    public function participationFor(?User $user): ?string
    {
        if (!$user || in_array($this->type_event, ['Annule', 'Annulé'], true)) {
            return null;
        }

        $ids = $this->users->pluck('id');

        if ($ids->contains($user->id)) {
            return 'named';
        }

        if ($user->is_equipe && $ids->contains(0)) {
            return 'team';
        }

        return null;
    }

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
