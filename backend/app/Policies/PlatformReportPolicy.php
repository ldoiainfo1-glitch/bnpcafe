<?php

namespace App\Policies;

use App\Models\User;

class PlatformReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isSubAdmin();
    }
}
