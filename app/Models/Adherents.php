<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adherents extends Model
{
    use HasFactory;

    protected $table = 'adherents';

    protected $fillable = [
        'nom_adh',
        'situation_adh',
        'dom_adh',
        'photo_adh',
        'type_adh',
        'date_adh',
        'isCGU',
        'isPresent',
        'isSigned',
    ];

    protected $casts = [
        'isCGU'     => 'boolean',
        'isPresent' => 'boolean',
        'isSigned'  => 'boolean',
        'date_adh'  => 'date',
    ];

    /**
     * Supprime le fichier physique de la photo (public/assets/adherents/...), si présent.
     */
    public function deletePhotoFile(): void
    {
        if (empty($this->photo_adh)) {
            return;
        }

        $path = public_path($this->photo_adh);

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
