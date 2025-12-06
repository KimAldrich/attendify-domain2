<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function manage(User $user, Event $event): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ((int) $event->owner_id === (int) $user->id) {
            return true;
        }

        return $event->userRoles()
            ->where('user_id', $user->id)
            ->where('role', 'co_organizer')
            ->exists();
    }
}
