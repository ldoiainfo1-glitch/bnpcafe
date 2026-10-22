<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case SubAdmin = 'sub_admin';
    case Member = 'member';
    case Guest = 'guest';
}
