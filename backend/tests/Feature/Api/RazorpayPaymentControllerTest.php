<?php

namespace Tests\Feature\Api;

use App\Enums\MembershipApplicationStatus;
use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Chapter;
use App\Models\Invoice;
use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RazorpayPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set([
            'services.razorpay.key_id' => 'rzp_test_key',
            'services.razorpay.key_secret' => 'test-key-secret',
            'services.razorpay.webhook_secret' => 'test-webhook-secret',
        ]);
    }

    public function test_member_can_create_order_and_repeated_requests_reuse_it(): void
    {
        $membership = $this->makePendingMembership();
        Sanctum::actingAs($membership->user);
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_test_001',
                'amount' => 120055,
                'currency' => 'INR',
            ]),
        ]);

        $url = '/api/memberships/'.$membership->id.'/payments/razorpay/order';
        $this->postJson($url)
            ->assertOk()
            ->assertJsonPath('key_id', 'rzp_test_key')
            ->assertJsonPath('order_id', 'order_test_001')
            ->assertJsonPath('amount', 120055)
            ->assertJsonPath('currency', 'INR');

        $this->postJson($url)
            ->assertOk()
            ->assertJsonPath('order_id', 'order_test_001');

        Http::assertSentCount(1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_confirmation_requires_provider_capture_and_activates_membership(): void
    {
        $membership = $this->makePendingMembership();
        Sanctum::actingAs($membership->user);
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_test_002',
                'amount' => 120055,
                'currency' => 'INR',
            ]),
            'https://api.razorpay.com/v1/payments/pay_test_002' => Http::response([
                'id' => 'pay_test_002',
                'order_id' => 'order_test_002',
                'amount' => 120055,
                'currency' => 'INR',
                'status' => 'captured',
            ]),
        ]);

        $this->postJson('/api/memberships/'.$membership->id.'/payments/razorpay/order')
            ->assertOk();

        $payload = $this->confirmationPayload('order_test_002', 'pay_test_002');
        $confirmUrl = '/api/memberships/'.$membership->id.'/payments/razorpay/confirm';

        $this->postJson($confirmUrl, $payload)
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath(
                'data.invoice.number',
                fn ($number) => preg_match('/^BNP-\d{4}-\d{8}$/', $number) === 1,
            );

        $this->postJson($confirmUrl, $payload)
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $membership->refresh();
        $this->assertSame(MembershipStatus::Active, $membership->status);
        $this->assertSame(UserRole::Member, $membership->user->fresh()->role);
        $this->assertSame($membership->chapter_id, $membership->user->chapter_id);
        $this->assertDatabaseCount('invoices', 1);

        $this->getJson('/api/invoices')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount_minor', 120055);
        $this->getJson('/api/payments')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'paid');
    }

    public function test_invalid_signature_does_not_call_gateway_or_activate_membership(): void
    {
        $membership = $this->makePendingMembership();
        $this->makePayment($membership, 'order_test_003');
        Sanctum::actingAs($membership->user);
        Http::fake();

        $this->postJson('/api/memberships/'.$membership->id.'/payments/razorpay/confirm', [
            'razorpay_order_id' => 'order_test_003',
            'razorpay_payment_id' => 'pay_test_003',
            'razorpay_signature' => str_repeat('0', 64),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('razorpay_signature');

        Http::assertNothingSent();
        $this->assertSame(MembershipStatus::PendingPayment, $membership->fresh()->status);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_payment_provider_amount_mismatch_is_rejected(): void
    {
        $membership = $this->makePendingMembership();
        $this->makePayment($membership, 'order_test_004');
        Sanctum::actingAs($membership->user);
        Http::fake([
            'https://api.razorpay.com/v1/payments/pay_test_004' => Http::response([
                'id' => 'pay_test_004',
                'order_id' => 'order_test_004',
                'amount' => 120054,
                'currency' => 'INR',
                'status' => 'captured',
            ]),
        ]);

        $this->postJson('/api/memberships/'.$membership->id.'/payments/razorpay/confirm', [
            ...$this->confirmationPayload('order_test_004', 'pay_test_004'),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment');

        $this->assertSame(MembershipStatus::PendingPayment, $membership->fresh()->status);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_signed_capture_webhook_activates_membership_idempotently(): void
    {
        $membership = $this->makePendingMembership();
        $payment = $this->makePayment($membership, 'order_webhook_005');
        $payload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_webhook_005',
                        'order_id' => 'order_webhook_005',
                        'amount' => $payment->amount_minor,
                        'currency' => 'INR',
                        'status' => 'captured',
                    ],
                ],
            ],
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $body, 'test-webhook-secret');

        $this->call('POST', '/api/payments/razorpay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
        ], $body)->assertNoContent();
        $this->call('POST', '/api/payments/razorpay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
        ], $body)->assertNoContent();

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(MembershipStatus::Active, $membership->fresh()->status);
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        $this->call('POST', '/api/payments/razorpay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => str_repeat('0', 64),
        ], '{"event":"payment.captured","payload":{}}')
            ->assertUnauthorized();
    }

    public function test_payment_order_requires_configuration_ownership_and_inr_price(): void
    {
        $membership = $this->makePendingMembership();
        $otherUser = User::factory()->create(['role' => UserRole::Guest]);
        Sanctum::actingAs($otherUser);

        $this->postJson('/api/memberships/'.$membership->id.'/payments/razorpay/order')
            ->assertNotFound();

        Sanctum::actingAs($membership->user);
        $nonInrMembership = $this->makePendingMembership(currency: 'USD');
        Sanctum::actingAs($nonInrMembership->user);
        $this->postJson('/api/memberships/'.$nonInrMembership->id.'/payments/razorpay/order')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('membership');

        Config::set('services.razorpay.key_id', null);
        $this->postJson('/api/memberships/'.$membership->id.'/payments/razorpay/order')
            ->assertServiceUnavailable();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_order_creation_provider_failure_is_recorded(): void
    {
        $membership = $this->makePendingMembership();
        Sanctum::actingAs($membership->user);
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response(['error' => 'invalid key'], 401),
        ]);

        $this->postJson('/api/memberships/'.$membership->id.'/payments/razorpay/order')
            ->assertStatus(502);

        $this->assertDatabaseHas('payments', [
            'membership_id' => $membership->id,
            'status' => 'failed',
            'failure_code' => 'provider_unavailable',
        ]);
    }

    public function test_financial_reconciliation_is_super_admin_only(): void
    {
        $membership = $this->makePendingMembership();
        $payment = $this->makePayment($membership, 'order_admin_006');
        Invoice::query()->create([
            'number' => 'BNP-2026-00009999',
            'payment_id' => $payment->id,
            'membership_id' => $membership->id,
            'user_id' => $membership->user_id,
            'amount_minor' => $payment->amount_minor,
            'currency' => $payment->currency,
            'issued_at' => now(),
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SubAdmin]));
        $this->getJson('/api/admin/payments')->assertForbidden();
        $this->getJson('/api/admin/invoices')->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $this->getJson('/api/admin/payments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.provider_order_id', 'order_admin_006');
        $this->getJson('/api/admin/invoices')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.number', 'BNP-2026-00009999');
    }

    private function makePendingMembership(
        string $price = '1200.55',
        string $currency = 'INR',
    ): Membership {
        $user = User::factory()->create(['role' => UserRole::Guest]);
        $chapter = Chapter::factory()->create();
        $plan = MembershipPlan::query()->create([
            'name' => fake()->unique()->words(2, true),
            'price' => $price,
            'currency' => $currency,
            'duration_months' => 12,
            'is_active' => true,
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

        return Membership::query()->create([
            'user_id' => $user->id,
            'membership_application_id' => $application->id,
            'membership_plan_id' => $plan->id,
            'chapter_id' => $chapter->id,
            'status' => MembershipStatus::PendingPayment,
        ]);
    }

    private function makePayment(Membership $membership, string $orderId): Payment
    {
        return Payment::query()->create([
            'membership_id' => $membership->id,
            'user_id' => $membership->user_id,
            'provider' => 'razorpay',
            'provider_order_id' => $orderId,
            'amount_minor' => 120055,
            'currency' => 'INR',
            'status' => PaymentStatus::Created,
        ]);
    }

    /**
     * @return array{razorpay_order_id: string, razorpay_payment_id: string, razorpay_signature: string}
     */
    private function confirmationPayload(string $orderId, string $paymentId): array
    {
        return [
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => hash_hmac(
                'sha256',
                $orderId.'|'.$paymentId,
                'test-key-secret',
            ),
        ];
    }
}
