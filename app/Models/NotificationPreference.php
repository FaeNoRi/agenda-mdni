<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Choix d'un utilisateur pour un type de notification désactivable. */
class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'type', 'actif'];

    protected $casts = ['actif' => 'boolean'];
}
