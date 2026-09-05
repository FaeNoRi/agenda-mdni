<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Horaire extends Model
{
    use HasFactory;

    protected $fillable = ['nom'];

    public function jours()
    {
        return $this->hasMany(HoraireJour::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'id_horaire');
    }

}
