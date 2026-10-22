<?php

namespace Tests\Feature;

use App\Enums\MembershipApplicationStatus;
use App\Enums\MembershipStatus;
use App\Enums\UserRole;
use App\Models\Chapter;
use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MembershipExpiryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_membership_downgrades_member_to_guest(): void
    {
        [$user, $membership] = $this->createMembership(now()->subDay());

        $this->artisan('memberships:expire')
            ->expectsOutput('Expired 1 membership(s).')
            ->assertExitCode(0);

        $this->assertSame(MembershipStatus::Expired, $membership->fresh()->status);
        $this->assertSame(UserRole::Guest, $user->fresh()->role);
        $this->assertNull($user->fresh()->chapter_id);
    }

    public function test_another_active_membership_preserves_member_access_and_chapter(): void
    {
        [$user, $expiredMembership] = $this->createMembership(now()->subDay());
        [, $activeMembership] = $this->createMembership(
            now()->addMonth(),
            $user,
        );

        $this->artisan('memberships:expire')
            ->expectsOutput('Expired 1 membership(s).')
            ->assertExitCode(0);

        $this->assertSame(MembershipStatus::Expired, $expiredMembership->fresh()->status);
        $this->assertSame(MembershipStatus::Active, $activeMembership->fresh()->status);
        $this->assertSame(UserRole::Member, $user->fresh()->role);
        $this->assertSame($activeMembership->chapter_id, $user->fresh()->chapter_id);
    }

    /**
     * @return array{User, Membership}
     */
    private function createMembership(Carbon $endsAt, ?User $user = null): array
    {
        $user ??= User::factory()->create(['role' => UserRole::Member]);
        $chapter = Chapter::factory()->create();
        $plan = MembershipPlan::query()->create([
            'name' => fake()->unique()->words(2, true),
            'price' => '1200.00',
            'currency' => 'INR',
            'duration_months' => 12,
        ]);
        $application = MembershipApplication::query()->create([
            'user_id' => $user->id,
            'chapter_id' => $chapter->id,
            'membership_plan_id' => $plan->id,
            'phone' => '+14155550100',
            'business_name' => 'Northwind',
            'industry' => 'Technology',
            'status' => MembershipApplicationStatus::Approved,
            'reviewed_at' => now(),
        ]);
        $membership = Membership::query()->create([
            'user_id' => $user->id,
            'membership_application_id' => $application->id,
            'membership_plan_id' => $plan->id,
            'chapter_id' => $chapter->id,
            'status' => MembershipStatus::Active,
            'starts_at' => now()->subYear(),
            'ends_at' => $endsAt,
        ]);
        $user->forceFill(['chapter_id' => $chapter->id])->save();

        return [$user, $membership];
    }
}
