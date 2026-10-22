<?php

namespace App\Policies;

use App\Models\Chapter;
use App\Models\User;

class ChapterPolicy
{
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isSubAdmin();
    }

    public function view(User $user, Chapter $chapter): bool
    {
        return $user->canAccessChapter($chapter);
    }

    public function update(User $user, Chapter $chapter): bool
    {
        return $user->isSuperAdmin();
    }
}
