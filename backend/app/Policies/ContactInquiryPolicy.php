<?php

namespace App\Policies;

use App\Models\ContactInquiry;
use App\Models\User;

class ContactInquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, ContactInquiry $inquiry): bool
    {
        return $user->isSuperAdmin();
    }
}
