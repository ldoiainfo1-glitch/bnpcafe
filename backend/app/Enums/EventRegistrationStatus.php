<?php

namespace App\Enums;

enum EventRegistrationStatus: string
{
    case Registered = 'registered';
    case Cancelled = 'cancelled';
}
