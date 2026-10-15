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

    /**
     * Adresse sans domaine (« /taches?tache=3 ») : un lien absolu bâti sur APP_URL peut pointer vers un autre
     * domaine que celui de la page (www, http/https, hôte local) ; le cookie de session n'y est alors pas envoyé
     * et l'on retombe sur la page de connexion. Les liens déjà enregistrés en absolu sont réécrits à l'affichage.
     */
    public static function cheminRelatif(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host'])) {
            return $url;
        }

        return ($parts['path'] ?? '/')
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNonVues(Builder $query): Builder
    {
        return $query->whereNull('vue_at');
    }
}
