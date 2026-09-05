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

    public function evenements()
    {
        return $this->belongsToMany(Evenements::class, 'evenement_users', 'user_id', 'evenement_id');
    }
}
