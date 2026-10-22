<?php

namespace Tests\Feature\Api;

use App\Enums\MembershipApplicationStatus;
use App\Enums\MembershipStatus;
use App\Enums\UserRole;
use App\Models\Chapter;
use App\Models\MemberProfile;
use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileDirectoryDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_read_and_update_only_their_own_profile(): void
    {
        $user = User::factory()->create(['role' => UserRole::Guest]);
        Sanctum::actingAs($user);

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->patchJson('/api/profile', [
            'phone' => '+1 (415) 555-0100',
            'business_name' => 'Northwind',
            'industry' => 'Technology',
            'website' => 'https://northwind.example',
            'directory_email' => ' PUBLIC@EXAMPLE.COM ',
            'show_in_directory' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.phone', '+14155550100')
            ->assertJsonPath('data.directory_email', 'public@example.com')
            ->assertJsonPath('data.show_in_directory', true);

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $user->id,
            'phone' => '+14155550100',
            'business_name' => 'Northwind',
            'show_in_directory' => true,
        ]);
    }

    public function test_profile_contact_details_are_never_exposed_without_directory_opt_in(): void
    {
        $user = $this->makeActiveMember(['show_in_directory' => false]);
        Sanctum::actingAs($user);

        $this->getJson('/api/directory')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_directory_lists_only_opted_in_members_with_active_membership(): void
    {
        $visible = $this->makeActiveMember([
            'show_in_directory' => true,
            'directory_email' => 'business@example.com',
            'directory_phone' => '+14155550100',
        ]);
        $this->makeActiveMember(['show_in_directory' => false]);
        $expired = $this->makeActiveMember(['show_in_directory' => true]);
        $expired->memberships()->update([
            'status' => MembershipStatus::Expired,
            'ends_at' => now()->subDay(),
        ]);
        Sanctum::actingAs($visible);

        $this->getJson('/api/directory')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id)
            ->assertJsonPath('data.0.email', 'business@example.com')
            ->assertJsonPath('data.0.phone', '+14155550100')
            ->assertJsonMissingPath('data.0.account_email');
    }

    public function test_guests_cannot_access_directory_but_sub_admins_are_chapter_scoped(): void
    {
        $assignedChapter = Chapter::factory()->create();
        $otherChapter = Chapter::factory()->create();
        $assignedMember = $this->makeActiveMember(['show_in_directory' => true], $assignedChapter);
        $otherMember = $this->makeActiveMember(['show_in_directory' => true], $otherChapter);
        $guest = User::factory()->create(['role' => UserRole::Guest]);
        Sanctum::actingAs($guest);
        $this->getJson('/api/directory')->assertForbidden();

        $subAdmin = User::factory()->create(['role' => UserRole::SubAdmin]);
        $subAdmin->administeredChapters()->attach($assignedChapter);
        Sanctum::actingAs($subAdmin);

        $this->getJson('/api/directory')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignedMember->id)
            ->assertJsonMissing(['id' => $otherMember->id]);
    }

    public function test_dashboard_returns_role_appropriate_data(): void
    {
        $guest = User::factory()->create(['role' => UserRole::Guest]);
        $application = $this->makeApplication($guest);
        Sanctum::actingAs($guest);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.role', 'guest')
            ->assertJsonPath('data.applications.0.id', $application->id)
            ->assertJsonPath('data.profile_complete', false)
            ->assertJsonPath('data.can_apply_for_membership', true)
            ->assertJsonPath('data.open_support_tickets', 0);

        $chapter = $application->chapter;
        $this->makeActiveMember([], $chapter);
        $this->makeActiveMember([], Chapter::factory()->create());
        $subAdmin = User::factory()->create(['role' => UserRole::SubAdmin]);
        $subAdmin->administeredChapters()->attach($chapter);
        Sanctum::actingAs($subAdmin);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.role', 'sub_admin')
            ->assertJsonPath('data.applications.submitted', 2)
            ->assertJsonPath('data.active_members', 1)
            ->assertJsonPath('data.upcoming_event_registrations', 0)
            ->assertJsonPath('data.open_support_tickets', 0)
            ->assertJsonPath('data.new_inquiries', null);
    }

    public function test_membership_application_populates_private_profile_fields(): void
    {
        $user = User::factory()->create(['role' => UserRole::Guest]);
        $chapter = Chapter::factory()->create();
        $plan = $this->makePlan();
        Sanctum::actingAs($user);

        $this->postJson('/api/membership-applications', [
            'chapter_id' => $chapter->id,
            'membership_plan_id' => $plan->id,
            'phone' => '+1 (415) 555-0100',
            'business_name' => 'Northwind',
            'industry' => 'Technology',
            'designation' => 'Founder',
            'business_address' => 'Private address',
        ])->assertCreated();

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $user->id,
            'phone' => '+14155550100',
            'business_name' => 'Northwind',
            'business_address' => 'Private address',
            'show_in_directory' => false,
        ]);
    }

    public function test_admin_roles_cannot_edit_member_profile(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->patchJson('/api/profile', [
            'business_name' => 'Admin attempt',
        ])->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $profileAttributes
     */
    private function makeActiveMember(
        array $profileAttributes = [],
        ?Chapter $chapter = null,
    ): User {
        $user = User::factory()->create(['role' => UserRole::Member]);
        $chapter ??= Chapter::factory()->create();
        $plan = $this->makePlan();
        $application = $this->makeApplication($user, $chapter, $plan);
        Membership::query()->create([
            'user_id' => $user->id,
            'membership_application_id' => $application->id,
            'membership_plan_id' => $plan->id,
            'chapter_id' => $chapter->id,
            'status' => MembershipStatus::Active,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
        ]);
        $user->forceFill(['chapter_id' => $chapter->id])->save();
        MemberProfile::query()->create(array_merge([
            'user_id' => $user->id,
            'business_name' => 'Northwind',
            'industry' => 'Technology',
            'show_in_directory' => false,
        ], $profileAttributes));

        return $user->fresh();
    }

    private function makeApplication(
        User $user,
        ?Chapter $chapter = null,
        ?MembershipPlan $plan = null,
    ): MembershipApplication {
        $chapter ??= Chapter::factory()->create();
        $plan ??= $this->makePlan();

        return MembershipApplication::query()->create([
            'user_id' => $user->id,
            'chapter_id' => $chapter->id,
            'membership_plan_id' => $plan->id,
            'phone' => '+14155550100',
            'business_name' => 'Northwind',
            'industry' => 'Technology',
            'status' => MembershipApplicationStatus::Submitted,
        ]);
    }

    private function makePlan(): MembershipPlan
    {
        return MembershipPlan::query()->create([
            'name' => fake()->unique()->words(2, true),
            'price' => '1200.00',
            'currency' => 'INR',
            'duration_months' => 12,
            'is_active' => true,
        ]);
    }
}
