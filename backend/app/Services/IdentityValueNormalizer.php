<?php

namespace App\Services;

use Illuminate\Support\Str;

class IdentityValueNormalizer
{
    public function email(string $email): string
    {
        return Str::lower(trim($email));
    }

    public function phone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        return preg_replace('/[\s().-]/', '', trim($phone));
    }
}
