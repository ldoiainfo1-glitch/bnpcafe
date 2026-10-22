<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class EventFeedbackPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Guest, UserRole::Member], true);
    }
}
