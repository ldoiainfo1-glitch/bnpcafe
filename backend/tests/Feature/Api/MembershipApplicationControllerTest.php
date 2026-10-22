<?php

namespace Tests\Feature\Api;

use App\Enums\MembershipApplicationStatus;
use App\Enums\MembershipStatus;
use App\Enums\UserRole;
use App\Models\Chapter;
use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MembershipApplicationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_apply_with_active_chapter_and_plan(): void
    {
        $applicant = User::factory()->create(['role' => UserRole::Guest]);
        $chapter = Chapter::factory()->create(['is_active' => true]);
        $plan = $this->createPlan();
        Sanctum::actingAs($applicant);

        $this->postJson('/api/membership-applications', [
            'chapter_id' => $chapter->id,
            'membership_plan_id' => $plan->id,
            'phone' => '+1 (415) 555-0100',
            'business_name' => 'Northwind',
            'industry' => 'Technology',
            'designation' => 'Founder',
            'business_address' => 'One Main Street',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.phone', '+14155550100')
            ->assertJsonPath('data.applicant.id', $applicant->id);

        $this->assertDatabaseHas('membership_applications', [
            'user_id' => $applicant->id,
            'chapter_id' => $chapter->id,
            'membership_plan_id' => $plan->id,
            'status' => 'submitted',
        ]);
    }

    public function test_applicant_can_only_view_their_own_applications_and_memberships(): void
    {
        $applicant = User::factory()->create(['role' => UserRole::Guest]);
        $otherApplicant = User::factory()->create(['role' => UserRole::Guest]);
        $application = $this->makeApplication($otherApplicant);
        Sanctum::actingAs($applicant);

        $this->getJson('/api/membership-applications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->getJson('/api/memberships')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseHas('membership_applications', [
            'id' => $application->id,
            'user_id' => $otherApplicant->id,
        ]);
    }

    public function test_duplicate_pending_applications_are_rejected(): void
    {
        $applicant = User::factory()->create(['role' => UserRole::Guest]);
        $this->makeApplication($applicant);
        Sanctum::actingAs($applicant);

        $this->postJson('/api/membership-applications', $this->applicationData())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('application');
    }

    public function test_existing_pending_payment_or_active_membership_prevents_a_second_application(): void
    {
        $applicant = User::factory()->create(['role' => UserRole::Guest]);
        $application = $this->makeApplication($applicant);
        $application->update(['status' => MembershipApplicationStatus::Approved]);
        Membership::query()->create([
            'user_id' => $applicant->id,
            'membership_application_id' => $application->id,
            'membership_plan_id' => $application->membership_plan_id,
            'chapter_id' => $application->chapter_id,
            'status' => MembershipStatus::PendingPayment,
        ]);
        Sanctum::actingAs($applicant);

        $this->postJson('/api/membership-applications', $this->applicationData())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('membership');
    }

    public function test_applications_require_active_chapter_and_plan(): void
    {
        $applicant = User::factory()->create(['role' => UserRole::Guest]);
        $chapter = Chapter::factory()->create(['is_active' => false]);
        $plan = $this->createPlan(['is_active' => false]);
        Sanctum::actingAs($applicant);

        $this->postJson('/api/membership-applications', array_merge(
            $this->applicationData(),
            ['chapter_id' => $chapter->id, 'membership_plan_id' => $plan->id],
        ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['chapter_id', 'membership_plan_id']);
    }

    public function test_admin_can_approve_application_and_create_pending_payment_membership(): void
    {
        $application = $this->makeApplication();
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        Sanctum::actingAs($admin);

        $response = $this->patchJson('/api/admin/membership-applications/'.$application->id, [
            'status' => 'approved',
            'review_note' => 'Approved subject to payment.',
        ]);

        $this->assertDatabaseHas('memberships', [
            'membership_application_id' => $application->id,
            'user_id' => $application->user_id,
            'status' => 'pending_payment',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.review_note', 'Approved subject to payment.')
            ->assertJsonPath('data.membership.status', 'pending_payment');

        $this->assertSame(UserRole::Guest, $application->applicant->fresh()->role);

        Sanctum::actingAs($application->applicant);
        $this->getJson('/api/memberships')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'pending_payment');
    }

    public function test_super_admin_can_view_all_membership_applications(): void
    {
        $application = $this->makeApplication();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->getJson('/api/admin/membership-applications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $application->id);
    }

    public function test_approval_of_a_free_plan_activates_membership_without_gateway_payment(): void
    {
        $application = $this->makeApplication(plan: $this->createPlan(['price' => '0.00']));
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->patchJson('/api/admin/membership-applications/'.$application->id, [
            'status' => 'approved',
        ])
            ->assertOk()
            ->assertJsonPath('data.membership.status', 'active');

        $this->assertSame(MembershipStatus::Active, $application->membership()->first()->status);
        $this->assertSame(UserRole::Member, $application->applicant->fresh()->role);
        $this->assertSame($application->chapter_id, $application->applicant->chapter_id);
    }

    public function test_review_status_transitions_are_restricted(): void
    {
        $application = $this->makeApplication();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->patchJson('/api/admin/membership-applications/'.$application->id, [
            'status' => 'under_review',
        ])->assertOk()->assertJsonPath('data.status', 'under_review');

        $this->patchJson('/api/admin/membership-applications/'.$application->id, [
            'status' => 'rejected',
            'review_note' => 'Incomplete business information.',
        ])->assertOk()->assertJsonPath('data.status', 'rejected');

        $this->patchJson('/api/admin/membership-applications/'.$application->id, [
            'status' => 'approved',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->assertDatabaseCount('memberships', 0);
    }

    public function test_sub_admin_only_sees_and_reviews_assigned_chapters(): void
    {
        $assignedChapter = Chapter::factory()->create();
        $unassignedChapter = Chapter::factory()->create();
        $assignedApplication = $this->makeApplication(chapter: $assignedChapter);
        $unassignedApplication = $this->makeApplication(chapter: $unassignedChapter);
        $subAdmin = User::factory()->create(['role' => UserRole::SubAdmin]);
        $subAdmin->administeredChapters()->attach($assignedChapter);
        Sanctum::actingAs($subAdmin);

        $this->getJson('/api/admin/membership-applications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignedApplication->id);

        $this->patchJson('/api/admin/membership-applications/'.$assignedApplication->id, [
            'status' => 'approved',
        ])->assertOk();

        $this->getJson('/api/admin/membership-applications/'.$unassignedApplication->id)
            ->assertForbidden();
        $this->patchJson('/api/admin/membership-applications/'.$unassignedApplication->id, [
            'status' => 'approved',
        ])->assertForbidden();
    }

    public function test_admins_cannot_submit_applications_and_guests_cannot_review(): void
    {
        $application = $this->makeApplication();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->postJson('/api/membership-applications', $this->applicationData())
            ->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Guest]));
        $this->getJson('/api/admin/membership-applications')->assertForbidden();
        $this->patchJson('/api/admin/membership-applications/'.$application->id, [
            'status' => 'approved',
        ])->assertForbidden();
    }

    public function test_nonexistent_and_invalid_application_fields_are_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Guest]));

        $this->postJson('/api/membership-applications', [
            'chapter_id' => 999999,
            'membership_plan_id' => 999999,
            'phone' => 'not a phone',
            'business_name' => '',
            'industry' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'chapter_id',
                'membership_plan_id',
                'phone',
                'business_name',
                'industry',
            ]);
    }

    private function makeApplication(
        ?User $applicant = null,
        ?Chapter $chapter = null,
        ?MembershipPlan $plan = null,
    ): MembershipApplication {
        $applicant ??= User::factory()->create(['role' => UserRole::Guest]);
        $chapter ??= Chapter::factory()->create();
        $plan ??= $this->createPlan();

        return MembershipApplication::query()->create(array_merge(
            $this->applicationData($chapter, $plan),
            ['user_id' => $applicant->id],
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationData(?Chapter $chapter = null, ?MembershipPlan $plan = null): array
    {
        $chapter ??= Chapter::factory()->create();
        $plan ??= $this->createPlan();

        return [
            'chapter_id' => $chapter->id,
            'membership_plan_id' => $plan->id,
            'phone' => '+14155550100',
            'business_name' => 'Northwind',
            'industry' => 'Technology',
            'designation' => 'Founder',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPlan(array $attributes = []): MembershipPlan
    {
        return MembershipPlan::query()->create(array_merge([
            'name' => fake()->unique()->words(2, true),
            'price' => '1200.00',
            'currency' => 'INR',
            'duration_months' => 12,
            'is_active' => true,
        ], $attributes));
    }
}
