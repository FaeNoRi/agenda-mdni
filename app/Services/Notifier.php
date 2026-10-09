<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Support\NotificationTypes;
use Illuminate\Support\Collection;

/**
 * Envoie des notifications internes. Les destinataires sont dédoublonnés ; la personne à l'origine
 * de l'action n'est jamais notifiée ; les types désactivables respectent les préférences du Profil.
 *
 * Données : titre (obligatoire), contexte (**gras** accepté), projet_nom, echeance, jalon, url,
 * cle (anti-doublon : une notification portant la même clé n'est créée qu'une fois par personne).
 */
class Notifier
{
    /**
     * @param iterable<User|int|null> $destinataires
     * @return int nombre de notifications créées
     */
    public function envoyer(iterable $destinataires, string $type, array $data, ?User $acteur = null): int
    {
        if (!NotificationTypes::existe($type)) {
            throw new \InvalidArgumentException("Type de notification inconnu : {$type}");
        }

        $ids = Collection::make($destinataires)
            ->map(fn ($u) => $u instanceof User ? $u->id : (int) $u)
            ->filter(fn ($id) => $id > 0 && $id !== $acteur?->id)   // 0 = « Toute l'équipe »
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        if (!NotificationTypes::imposee($type)) {
            $refus = NotificationPreference::where('type', $type)->where('actif', false)->whereIn('user_id', $ids)->pluck('user_id');
            $ids = $ids->diff($refus)->values();
        }

        $cle = isset($data['cle']) ? $type.':'.$data['cle'] : null;
        $cree = 0;

        foreach (User::whereIn('id', $ids)->pluck('id') as $userId) {
            if ($cle && AppNotification::where('user_id', $userId)->where('cle', $cle)->exists()) {
                continue;
            }

            AppNotification::create([
                'user_id' => $userId,
                'type' => $type,
                'categorie' => NotificationTypes::categorie($type),
                'titre' => $data['titre'],
                'contexte' => $data['contexte'] ?? null,
                'projet_nom' => $data['projet_nom'] ?? null,
                'echeance' => $data['echeance'] ?? null,
                'jalon' => $data['jalon'] ?? null,
                'url' => $data['url'] ?? null,
                'cle' => $cle,
            ]);
            $cree++;
        }

        return $cree;
    }
}
