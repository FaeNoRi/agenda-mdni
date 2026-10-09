<?php

namespace App\Policies;

use App\Models\AppNotification;
use App\Models\User;

/** Une notification n'appartient qu'à son destinataire. */
class AppNotificationPolicy
{
    public function update(User $user, AppNotification $notification): bool
    {
        return $notification->user_id === $user->id;
    }
}
