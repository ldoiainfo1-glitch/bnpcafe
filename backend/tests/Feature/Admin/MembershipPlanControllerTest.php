<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MembershipPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_listing_returns_only_active_plans_in_display_order(): void
    {
        $laterPlan = $this->createPlan(['name' => 'Later', 'sort_order' => 2]);
        $firstPlan = $this->createPlan(['name' => 'First', 'sort_order' => 1]);
        $this->createPlan(['name' => 'Inactive', 'is_active' => false, 'sort_order' => 0]);

        $this->getJson('/api/membership-plans')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $firstPlan->id)
            ->assertJsonPath('data.1.id', $laterPlan->id)
            ->assertJsonPath('data.0.currency', 'INR')
            ->assertJsonPath('data.0.features.0', 'Networking events');
    }

    public function test_super_admin_can_create_and_update_a_plan(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->postJson('/api/admin/membership-plans', [
            'name' => 'Annual',
            'description' => 'Annual membership',
            'price' => '1200.00',
            'currency' => 'inr',
            'duration_months' => 12,
            'features' => ['Networking events'],
            'sort_order' => 1,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Annual')
            ->assertJsonPath('data.currency', 'INR')
            ->assertJsonPath('data.is_active', true);

        $plan = MembershipPlan::query()->firstOrFail();

        $this->patchJson('/api/admin/membership-plans/'.$plan->id, [
            'price' => '1500.00',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.price', '1500.00')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_plan_writes_validate_price_duration_and_features(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->postJson('/api/admin/membership-plans', [
            'name' => 'Invalid',
            'price' => '-1.00',
            'duration_months' => 0,
            'features' => [''],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price', 'duration_months', 'features.0']);
    }

    public function test_only_super_admin_can_manage_plans(): void
    {
        foreach ([UserRole::SubAdmin, UserRole::Member, UserRole::Guest] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));

            $this->getJson('/api/admin/membership-plans')->assertForbidden();
            $this->postJson('/api/admin/membership-plans', [])->assertForbidden();
        }
    }

    public function test_unauthenticated_admin_plan_requests_are_rejected(): void
    {
        $this->getJson('/api/admin/membership-plans')->assertUnauthorized();
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
            'features' => ['Networking events'],
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));
    }
}
