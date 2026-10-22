<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\IdentityVerificationChallenge;
use App\Models\User;
use App\Models\UserIdentity;
use App\Notifications\GuestAccessCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class GuestIdentityClaimService
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly IdentityValueNormalizer $normalizer) {}

    public function begin(string $name, string $email, ?string $phone): void
    {
        $email = $this->normalizer->email($email);
        $phone = $this->normalizer->phone($phone);
        $code = (string) random_int(100000, 999999);

        $challengeCreated = DB::transaction(function () use ($name, $email, $phone, $code): bool {
            $emailIdentity = UserIdentity::query()
                ->where('type', 'email')
                ->where('normalized_value', $email)
                ->first();

            $phoneIdentity = $phone === null
                ? null
                : UserIdentity::query()
                    ->where('type', 'phone')
                    ->where('normalized_value', $phone)
                    ->first();

            $emailUser = $emailIdentity?->user
                ?? User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            if ($phoneIdentity !== null
                && ($emailUser === null || $phoneIdentity->user_id !== $emailUser->id)) {
                return false;
            }

            IdentityVerificationChallenge::query()
                ->where('email', $email)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            IdentityVerificationChallenge::query()->create([
                'email' => $email,
                'name' => $name,
                'phone' => $phone,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
            ]);

            return true;
        });

        if ($challengeCreated) {
            Notification::route('mail', $email)
                ->notify(new GuestAccessCodeNotification($code));
        }
    }

    public function verify(string $email, string $code): ?User
    {
        $email = $this->normalizer->email($email);

        return DB::transaction(function () use ($email, $code): ?User {
            $challenge = IdentityVerificationChallenge::query()
                ->where('email', $email)
                ->whereNull('consumed_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($challenge === null
                || $challenge->expires_at->isPast()
                || $challenge->attempts >= self::MAX_ATTEMPTS) {
                if ($challenge !== null) {
                    $challenge->update(['consumed_at' => now()]);
                }

                return null;
            }

            if (! Hash::check($code, $challenge->code_hash)) {
                $attempts = $challenge->attempts + 1;
                $challenge->update([
                    'attempts' => $attempts,
                    'consumed_at' => $attempts >= self::MAX_ATTEMPTS ? now() : null,
                ]);

                return null;
            }

            $emailIdentity = UserIdentity::query()
                ->where('type', 'email')
                ->where('normalized_value', $email)
                ->lockForUpdate()
                ->first();

            $user = $emailIdentity?->user
                ?? User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            $phoneIdentity = $challenge->phone === null
                ? null
                : UserIdentity::query()
                    ->where('type', 'phone')
                    ->where('normalized_value', $challenge->phone)
                    ->lockForUpdate()
                    ->first();

            if ($phoneIdentity !== null
                && ($user === null || $phoneIdentity->user_id !== $user->id)) {
                $challenge->update(['consumed_at' => now()]);

                return null;
            }

            if ($user === null) {
                $user = new User;
                $user->name = $challenge->name;
                $user->email = $email;
                $user->password = Str::random(64);
                $user->role = UserRole::Guest;
                $user->save();
            }

            $user->forceFill(['email_verified_at' => now()])->save();

            UserIdentity::query()->updateOrCreate(
                ['type' => 'email', 'normalized_value' => $email],
                ['user_id' => $user->id, 'verified_at' => now()],
            );

            if ($challenge->phone !== null && $phoneIdentity === null) {
                UserIdentity::query()->create([
                    'user_id' => $user->id,
                    'type' => 'phone',
                    'normalized_value' => $challenge->phone,
                    'verified_at' => null,
                ]);
            }

            $challenge->update(['consumed_at' => now()]);

            return $user;
        });
    }
}
