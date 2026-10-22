<?php

namespace App\Console\Commands;

use App\Enums\MembershipStatus;
use App\Enums\UserRole;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireMemberships extends Command
{
    protected $signature = 'memberships:expire';

    protected $description = 'Expire memberships whose end date has passed';

    public function handle(): int
    {
        $expiredCount = 0;

        Membership::query()
            ->where('status', MembershipStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->chunkById(100, function ($memberships) use (&$expiredCount): void {
                foreach ($memberships as $candidate) {
                    DB::transaction(function () use ($candidate, &$expiredCount): void {
                        $membership = Membership::query()
                            ->lockForUpdate()
                            ->find($candidate->id);

                        if ($membership === null
                            || $membership->status !== MembershipStatus::Active
                            || $membership->ends_at === null
                            || $membership->ends_at->isFuture()) {
                            return;
                        }

                        $membership->update(['status' => MembershipStatus::Expired]);
                        $user = User::query()->lockForUpdate()->find($membership->user_id);
                        if ($user === null) {
                            return;
                        }

                        $activeMembership = $user->memberships()
                            ->where('status', MembershipStatus::Active)
                            ->orderByDesc('ends_at')
                            ->first();

                        if ($activeMembership !== null) {
                            $user->forceFill([
                                'role' => UserRole::Member,
                                'chapter_id' => $activeMembership->chapter_id,
                            ])->save();
                        } elseif ($user->role === UserRole::Member) {
                            $user->forceFill([
                                'role' => UserRole::Guest,
                                'chapter_id' => null,
                            ])->save();
                        }

                        $expiredCount++;
                    });
                }
            });

        $this->info("Expired {$expiredCount} membership(s).");

        return self::SUCCESS;
    }
}
