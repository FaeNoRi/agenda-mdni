<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_equipe',
        'is_civique',
        'id_horaire',
        'is_email',
        'theme',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_equipe' => 'boolean',
            'is_civique' => 'boolean',
            'id_horaire' => 'integer',
        ];
    }

    /**
     * Relation avec la table horaires (optionnel).
     */
    public function horaire()
    {
        return $this->belongsTo(Horaire::class, 'id_horaire');
    }

    /**
     * Vraies personnes (filtres, affectations) : ni « Toute l'équipe » (id 0), ni l'entrée « Non ».
     */
    public function scopePersonnes($query)
    {
        return $query->where('id', '!=', 0)->where('name', '!=', 'Non');
    }

    /** Tâches dont la personne est responsable. */
    public function tachesResponsable()
    {
        return $this->belongsToMany(Tache::class, 'tache_user');
    }

    /** Projets auxquels la personne participe (référent ou impliquée). */
    public function projets()
    {
        return $this->belongsToMany(Projet::class, 'projet_user')->withPivot('role');
    }

    public function evenements()
    {
        return $this->belongsToMany(Evenements::class, 'evenement_users', 'user_id', 'evenement_id');
    }
}
