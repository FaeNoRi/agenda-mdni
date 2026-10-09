<?php

namespace App\Policies;

use App\Models\Tache;
use App\Models\User;

/**
 * Droits sur les tâches.
 *  - consulter : tout le monde ;
 *  - créer / modifier : tous sauf le Service civique ;
 *  - changer le statut et commenter : tout le monde, le Service civique seulement sur une tâche
 *    où il est impliqué (responsable ou référent) ;
 *  - supprimer : administrateurs et référents (projet, ou créateur pour une tâche simple).
 */
class TachePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tache $tache): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return !$user->is_civique;
    }

    public function update(User $user, Tache $tache): bool
    {
        return !$user->is_civique;
    }

    public function delete(User $user, Tache $tache): bool
    {
        return !$user->is_civique && ($user->is_admin || $tache->estReferent($user));
    }

    public function changeStatus(User $user, Tache $tache): bool
    {
        return !$user->is_civique || $tache->estImplique($user);
    }

    public function comment(User $user, Tache $tache): bool
    {
        return !$user->is_civique || $tache->estImplique($user);
    }

    /** Valider une tâche « À valider » : administrateurs et référents. */
    public function valider(User $user, Tache $tache): bool
    {
        return !$user->is_civique && ($user->is_admin || $tache->estReferent($user));
    }
}
