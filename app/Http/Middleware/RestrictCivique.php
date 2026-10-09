<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Statut « Service civique » : consultation uniquement.
 * Toute écriture (POST/PUT/PATCH/DELETE) et tout formulaire de création / modification
 * sont refusés côté serveur, quel que soit ce qu'affiche l'interface.
 * Exceptions : déconnexion, préférences du compte et exports (lecture seule).
 */
class RestrictCivique
{
    /** Routes autorisées même en écriture (noms de routes). */
    private const ALLOWED = [
        'logout',
        'profile.update',
        'password.update',
        'user.theme.color',
        'export',
        // Projets & tâches : le Service civique peut changer le statut d'une tâche et commenter.
        // La règle fine (« uniquement là où il est impliqué ») est portée par les politiques
        // TachePolicy / ProjetPolicy, que les contrôleurs doivent interroger.
        'taches.statut',
        'taches.commentaires.store',
        'projets.commentaires.store',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_civique) {
            return $next($request);
        }

        $name = (string) $request->route()?->getName();

        if (in_array($name, self::ALLOWED, true)) {
            return $next($request);
        }

        $readOnly = $request->isMethodSafe();
        $isForm = str_ends_with($name, '.create') || str_ends_with($name, '.edit') || $name === 'evenements.duplicate';

        if ($readOnly && !$isForm) {
            return $next($request);
        }

        $message = 'Votre statut « Service civique » est en consultation uniquement.';

        abort_if($request->expectsJson() || $request->ajax(), 403, $message);
        abort(403, $message);
    }
}
