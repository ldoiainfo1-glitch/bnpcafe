<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Creating = 'creating';
    case Created = 'created';
    case Paid = 'paid';
    case Failed = 'failed';
}
