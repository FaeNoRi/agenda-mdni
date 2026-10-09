<?php

namespace App\Policies;

use App\Models\Projet;
use App\Models\User;

/**
 * Droits sur les projets.
 *  - consulter : tout le monde ;
 *  - créer / modifier : tous sauf le Service civique ;
 *  - supprimer : administrateurs et référents du projet ;
 *  - commenter : tout le monde, le Service civique seulement sur un projet où il est impliqué.
 */
class ProjetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Projet $projet): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return !$user->is_civique;
    }

    public function update(User $user, Projet $projet): bool
    {
        return !$user->is_civique;
    }

    public function delete(User $user, Projet $projet): bool
    {
        return !$user->is_civique && ($user->is_admin || $projet->estReferent($user));
    }

    public function comment(User $user, Projet $projet): bool
    {
        return !$user->is_civique || $projet->estImplique($user);
    }

    /** Valider un projet « À valider » : administrateurs et référents. */
    public function valider(User $user, Projet $projet): bool
    {
        return !$user->is_civique && ($user->is_admin || $projet->estReferent($user));
    }
}
