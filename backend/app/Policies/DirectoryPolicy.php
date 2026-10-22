<?php

namespace App\Policies;

use App\Enums\MembershipStatus;
use App\Enums\UserRole;
use App\Models\User;

class DirectoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isSubAdmin()
            || ($user->role === UserRole::Member
                && $user->memberships()
                    ->where('status', MembershipStatus::Active)
                    ->where('ends_at', '>', now())
                    ->exists());
    }
}
