<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case PendingPayment = 'pending_payment';
    case Active = 'active';
    case Expired = 'expired';
    case Suspended = 'suspended';
}
