<?php

namespace App\Policies;

use App\Models\User;

class SubAdminPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $subAdmin): bool
    {
        return $user->isSuperAdmin() && $subAdmin->isSubAdmin();
    }
}
