<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isSubAdmin();
    }

    public function view(User $user, Event $event): bool
    {
        return $user->isSuperAdmin()
            || ($user->isSubAdmin()
                && $event->chapter_id !== null
                && $user->administeredChapters()->whereKey($event->chapter_id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isSuperAdmin();
    }
}
