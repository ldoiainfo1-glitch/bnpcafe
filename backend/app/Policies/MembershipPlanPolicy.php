<?php

namespace App\Policies;

use App\Models\MembershipPlan;
use App\Models\User;

class MembershipPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, MembershipPlan $membershipPlan): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, MembershipPlan $membershipPlan): bool
    {
        return $user->isSuperAdmin();
    }
}
