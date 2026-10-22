<?php

namespace App\Enums;

enum ContactInquiryStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
}
