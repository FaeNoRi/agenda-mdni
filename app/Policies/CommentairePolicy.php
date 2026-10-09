<?php

namespace App\Policies;

use App\Models\Commentaire;
use App\Models\User;

/** Modifier ou supprimer un commentaire : son auteur ou un administrateur (jamais le Service civique). */
class CommentairePolicy
{
    public function update(User $user, Commentaire $commentaire): bool
    {
        return !$user->is_civique && $commentaire->user_id === $user->id;
    }

    public function delete(User $user, Commentaire $commentaire): bool
    {
        return !$user->is_civique && ($user->is_admin || $commentaire->user_id === $user->id);
    }
}
