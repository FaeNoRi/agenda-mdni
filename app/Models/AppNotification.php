<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Notification interne d'un utilisateur (voir App\Services\Notifier). */
class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id', 'type', 'categorie', 'titre', 'contexte', 'projet_nom', 'echeance', 'jalon', 'url', 'cle', 'vue_at',
    ];

    protected $casts = ['echeance' => 'date', 'vue_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNonVues(Builder $query): Builder
    {
        return $query->whereNull('vue_at');
    }
}
