<?php

namespace App\Policies;

use App\Models\User;

class PlatformSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function manage(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
