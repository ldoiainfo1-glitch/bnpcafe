<?php

namespace App\Enums;

enum SupportTicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
    case Closed = 'closed';
}
