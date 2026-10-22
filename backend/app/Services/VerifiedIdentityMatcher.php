<?php

namespace App\Services;

use App\Models\User;

class VerifiedIdentityMatcher
{
    public function __construct(private readonly IdentityValueNormalizer $normalizer) {}

    public function match(?User $authenticatedUser, string $email): ?User
    {
        if ($authenticatedUser === null) {
            return null;
        }

        $email = $this->normalizer->email($email);

        if ($authenticatedUser->email_verified_at !== null
            && $this->normalizer->email($authenticatedUser->email) === $email) {
            return $authenticatedUser;
        }

        return $authenticatedUser->identities()
            ->where('type', 'email')
            ->where('normalized_value', $email)
            ->whereNotNull('verified_at')
            ->exists()
                ? $authenticatedUser
                : null;
    }
}
