<?php

namespace Tests\Feature\Admin;

use App\Enums\ContentPageStatus;
use App\Enums\MembershipApplicationStatus;
use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Chapter;
use App\Models\ContactInquiry;
use App\Models\ContentPage;
use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentSettingsReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_publish_pages_and_public_api_hides_drafts(): void
    {
        $draft = ContentPage::query()->create([
            'slug' => 'draft-page',
            'title' => 'Draft',
            'body' => 'Not public.',
            'status' => ContentPageStatus::Draft,
        ]);

        $this->getJson('/api/content/pages')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/content/pages/draft-page')->assertNotFound();

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $this->patchJson('/api/admin/content/pages/'.$draft->id, [
            'status' => 'published',
            'title' => 'Published title',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->getJson('/api/content/pages/draft-page')
            ->assertOk()
            ->assertJsonPath('data.title', 'Published title')
            ->assertJsonPath('data.body', 'Not public.');
    }

    public function test_only_super_admin_can_manage_content_pages(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SubAdmin]));

        $this->getJson('/api/admin/content/pages')->assertForbidden();
        $this->postJson('/api/admin/content/pages', [
            'slug' => 'membership',
            'title' => 'Membership',
            'body' => 'Details.',
        ])->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $this->postJson('/api/admin/content/pages', [
            'slug' => 'membership',
            'title' => 'Membership',
            'body' => 'Details.',
        ])->assertCreated()->assertJsonPath('data.status', 'draft');
    }

    public function test_public_settings_are_allowlisted_and_managed_by_super_admin(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SubAdmin]));
        $this->patchJson('/api/admin/settings/support_email', [
            'value' => 'help@example.com',
        ])->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $this->patchJson('/api/admin/settings/support_email', [
            'value' => ' HELP@EXAMPLE.COM ',
        ])->assertOk()->assertJsonPath('data.key', 'support_email');
        $this->patchJson('/api/admin/settings/payment_secret', [
            'value' => 'must-not-be-stored',
        ])->assertUnprocessable()->assertJsonValidationErrors('value');

        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('data.support_email', 'help@example.com');
    }

    public function test_super_admin_report_includes_payment_revenue(): void
    {
        $chapter = Chapter::factory()->create();
        [$membership, $payment, $user] = $this->makeFinancialRecords($chapter);
        Payment::query()->whereKey($payment->id)->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
        ContactInquiry::query()->create([
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'subject' => 'Question',
            'message' => 'Please contact me.',
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->getJson('/api/admin/reports/overview?from='.now()->subDay()->toDateString().'&to='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.active_members', 0)
            ->assertJsonPath('data.revenue_by_currency.0.currency', 'INR')
            ->assertJsonPath('data.revenue_by_currency.0.amount_minor', 120000)
            ->assertJsonPath('data.new_inquiries', 1);

        $this->assertSame(MembershipStatus::PendingPayment, $membership->fresh()->status);
        $this->assertSame(UserRole::Guest, $user->fresh()->role);
    }

    public function test_sub_admin_report_is_chapter_scoped_and_excludes_financial_data(): void
    {
        $assignedChapter = Chapter::factory()->create();
        $otherChapter = Chapter::factory()->create();
        $this->makeFinancialRecords($assignedChapter);
        $this->makeFinancialRecords($otherChapter);

        $subAdmin = User::factory()->create(['role' => UserRole::SubAdmin]);
        $subAdmin->administeredChapters()->attach($assignedChapter);
        Sanctum::actingAs($subAdmin);

        $this->getJson('/api/admin/reports/overview')
            ->assertOk()
            ->assertJsonPath('data.active_members', 0)
            ->assertJsonPath('data.applications_received', 1)
            ->assertJsonPath('data.revenue_by_currency', null)
            ->assertJsonPath('data.new_inquiries', null);
    }

    public function test_report_rejects_invalid_or_excessive_date_ranges(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->getJson('/api/admin/reports/overview?from=2026-02-01&to=2026-01-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');
        $this->getJson('/api/admin/reports/overview?from=2024-01-01&to=2026-01-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');
    }

    /**
     * @return array{Membership, Payment, User}
     */
    private function makeFinancialRecords(Chapter $chapter): array
    {
        $user = User::factory()->create(['role' => UserRole::Guest]);
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
            'created_at' => now(),
        ]);
        $membership = Membership::query()->create([
            'user_id' => $user->id,
            'membership_application_id' => $application->id,
            'membership_plan_id' => $plan->id,
            'chapter_id' => $chapter->id,
            'status' => MembershipStatus::PendingPayment,
        ]);
        $payment = Payment::query()->create([
            'membership_id' => $membership->id,
            'user_id' => $user->id,
            'provider' => 'razorpay',
            'provider_order_id' => 'order-report-'.$user->id,
            'amount_minor' => 120000,
            'currency' => 'INR',
            'status' => PaymentStatus::Created,
        ]);

        return [$membership, $payment, $user];
    }
}
