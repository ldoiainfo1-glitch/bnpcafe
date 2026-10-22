<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MembershipApplication;
use App\Models\User;

class MembershipApplicationPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Guest, UserRole::Member], true);
    }

    public function viewOwn(User $user, MembershipApplication $application): bool
    {
        return $application->user_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isSubAdmin();
    }

    public function view(User $user, MembershipApplication $application): bool
    {
        return $user->isSuperAdmin()
            || ($user->isSubAdmin()
                && $user->administeredChapters()
                    ->whereKey($application->chapter_id)
                    ->exists());
    }

    public function review(User $user, MembershipApplication $application): bool
    {
        return $this->view($user, $application);
    }
}
