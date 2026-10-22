<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Guest, UserRole::Member], true);
    }

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isSubAdmin();
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $ticket->requester_id === $user->id
            || $this->canManage($user, $ticket);
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        return $ticket->requester_id === $user->id
            || $this->canManage($user, $ticket);
    }

    public function manage(User $user, SupportTicket $ticket): bool
    {
        return $this->canManage($user, $ticket);
    }

    private function canManage(User $user, SupportTicket $ticket): bool
    {
        return $user->isSuperAdmin()
            || ($user->isSubAdmin()
                && $ticket->chapter_id !== null
                && $user->administeredChapters()->whereKey($ticket->chapter_id)->exists());
    }
}
