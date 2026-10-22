<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\IdentityVerificationChallenge;
use App\Models\User;
use App\Models\UserIdentity;
use App\Notifications\GuestAccessCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GuestAccessControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Origin', 'http://localhost:5173');
    }

    public function test_new_guest_is_created_only_after_email_verification(): void
    {
        Notification::fake();

        $this->postJson('/api/guest/access', [
            'name' => 'New Guest',
            'email' => ' Guest@Example.Test ',
            'phone' => '+1 (415) 555-0134',
        ])->assertStatus(202);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseHas('identity_verification_challenges', [
            'email' => 'guest@example.test',
            'phone' => '+14155550134',
        ]);

        $code = $this->sentCodeTo('guest@example.test');

        $this->postJson('/api/guest/access/verify', [
            'email' => 'GUEST@example.test',
            'code' => $code,
        ])->assertOk()
            ->assertJsonPath('data.email', 'guest@example.test')
            ->assertJsonPath('data.role', UserRole::Guest->value);

        $user = User::query()->where('email', 'guest@example.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('user_identities', [
            'user_id' => $user->id,
            'type' => 'email',
            'normalized_value' => 'guest@example.test',
        ]);
        $this->assertDatabaseHas('user_identities', [
            'user_id' => $user->id,
            'type' => 'phone',
            'normalized_value' => '+14155550134',
            'verified_at' => null,
        ]);
    }

    public function test_verified_email_claim_signs_in_existing_account_without_changing_its_role(): void
    {
        Notification::fake();
        $member = User::factory()->create([
            'name' => 'Existing Member',
            'email' => 'member@example.test',
            'role' => UserRole::Member,
            'password' => Hash::make('existing-password'),
        ]);

        UserIdentity::query()->create([
            'user_id' => $member->id,
            'type' => 'email',
            'normalized_value' => 'member@example.test',
            'verified_at' => now(),
        ]);

        $this->postJson('/api/guest/access', [
            'name' => 'Untrusted Replacement Name',
            'email' => 'MEMBER@example.test',
        ])->assertStatus(202);

        $code = $this->sentCodeTo('member@example.test');

        $this->postJson('/api/guest/access/verify', [
            'email' => 'member@example.test',
            'code' => $code,
        ])->assertOk()
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.name', 'Existing Member')
            ->assertJsonPath('data.role', UserRole::Member->value);

        $this->assertAuthenticatedAs($member);
        $this->assertTrue(Hash::check('existing-password', $member->fresh()->password));
        $this->assertSame(1, User::query()->count());
    }

    public function test_email_claim_reuses_legacy_user_without_an_identity_record(): void
    {
        Notification::fake();
        $member = User::factory()->create([
            'email' => 'legacy@example.test',
            'role' => UserRole::Member,
        ]);

        $this->postJson('/api/guest/access', [
            'name' => 'Legacy Account',
            'email' => 'LEGACY@example.test',
        ])->assertStatus(202);

        $code = $this->sentCodeTo('legacy@example.test');

        $this->postJson('/api/guest/access/verify', [
            'email' => 'legacy@example.test',
            'code' => $code,
        ])->assertOk()
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.role', UserRole::Member->value);

        $this->assertSame(1, User::query()->count());
        $this->assertDatabaseHas('user_identities', [
            'user_id' => $member->id,
            'type' => 'email',
            'normalized_value' => 'legacy@example.test',
        ]);
    }

    public function test_phone_only_match_does_not_send_a_code_or_claim_an_existing_account(): void
    {
        Notification::fake();
        $member = User::factory()->create(['role' => UserRole::Member]);

        UserIdentity::query()->create([
            'user_id' => $member->id,
            'type' => 'email',
            'normalized_value' => $member->email,
            'verified_at' => now(),
        ]);
        UserIdentity::query()->create([
            'user_id' => $member->id,
            'type' => 'phone',
            'normalized_value' => '+14155550134',
            'verified_at' => null,
        ]);

        $this->postJson('/api/guest/access', [
            'name' => 'Claim Attempt',
            'email' => 'attacker@example.test',
            'phone' => '+1 415-555-0134',
        ])->assertStatus(202);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('identity_verification_challenges', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_conflicting_email_and_phone_identities_do_not_receive_a_code(): void
    {
        Notification::fake();
        $emailOwner = User::factory()->create(['email' => 'owner@example.test']);
        $phoneOwner = User::factory()->create(['email' => 'other@example.test']);

        UserIdentity::query()->create([
            'user_id' => $emailOwner->id,
            'type' => 'email',
            'normalized_value' => 'owner@example.test',
            'verified_at' => now(),
        ]);
        UserIdentity::query()->create([
            'user_id' => $phoneOwner->id,
            'type' => 'phone',
            'normalized_value' => '+14155550134',
            'verified_at' => now(),
        ]);

        $this->postJson('/api/guest/access', [
            'name' => 'Claim Attempt',
            'email' => 'owner@example.test',
            'phone' => '+14155550134',
        ])->assertStatus(202);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('identity_verification_challenges', 0);
    }

    public function test_invalid_code_is_rejected_and_attempt_is_recorded(): void
    {
        Notification::fake();

        $this->postJson('/api/guest/access', [
            'name' => 'New Guest',
            'email' => 'guest@example.test',
        ])->assertStatus(202);

        $this->postJson('/api/guest/access/verify', [
            'email' => 'guest@example.test',
            'code' => '000000',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->assertDatabaseHas('identity_verification_challenges', [
            'email' => 'guest@example.test',
            'attempts' => 1,
        ]);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_challenge_expires_and_cannot_be_replayed(): void
    {
        Notification::fake();
        $this->travelTo(now()->startOfSecond());

        $this->postJson('/api/guest/access', [
            'name' => 'New Guest',
            'email' => 'guest@example.test',
        ])->assertStatus(202);

        $code = $this->sentCodeTo('guest@example.test');
        $this->travel(11)->minutes();

        $this->postJson('/api/guest/access/verify', [
            'email' => 'guest@example.test',
            'code' => $code,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->assertDatabaseCount('users', 0);

        $this->travelBack();
    }

    public function test_fifth_invalid_code_consumes_the_challenge(): void
    {
        Notification::fake();

        $this->postJson('/api/guest/access', [
            'name' => 'New Guest',
            'email' => 'guest@example.test',
        ])->assertStatus(202);

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/guest/access/verify', [
                'email' => 'guest@example.test',
                'code' => '000000',
            ])->assertUnprocessable();
        }

        $challenge = IdentityVerificationChallenge::query()->firstOrFail();

        $this->assertSame(5, $challenge->attempts);
        $this->assertNotNull($challenge->consumed_at);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_expired_and_consumed_challenges_are_pruned_but_active_challenges_remain(): void
    {
        $expired = IdentityVerificationChallenge::query()->create([
            'email' => 'expired@example.test',
            'name' => 'Expired Guest',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinute(),
        ]);
        $consumed = IdentityVerificationChallenge::query()->create([
            'email' => 'consumed@example.test',
            'name' => 'Consumed Guest',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'consumed_at' => now(),
        ]);
        $active = IdentityVerificationChallenge::query()->create([
            'email' => 'active@example.test',
            'name' => 'Active Guest',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->artisan('model:prune', [
            '--model' => IdentityVerificationChallenge::class,
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('identity_verification_challenges', ['id' => $expired->id]);
        $this->assertDatabaseMissing('identity_verification_challenges', ['id' => $consumed->id]);
        $this->assertDatabaseHas('identity_verification_challenges', ['id' => $active->id]);
    }

    public function test_start_response_does_not_disclose_whether_an_email_or_phone_matches(): void
    {
        Notification::fake();
        $existingUser = User::factory()->create(['email' => 'known@example.test']);
        UserIdentity::query()->create([
            'user_id' => $existingUser->id,
            'type' => 'email',
            'normalized_value' => 'known@example.test',
            'verified_at' => now(),
        ]);
        UserIdentity::query()->create([
            'user_id' => $existingUser->id,
            'type' => 'phone',
            'normalized_value' => '+14155550134',
            'verified_at' => null,
        ]);

        $knownResponse = $this->postJson('/api/guest/access', [
            'name' => 'Guest',
            'email' => 'known@example.test',
        ]);
        $unknownResponse = $this->postJson('/api/guest/access', [
            'name' => 'Guest',
            'email' => 'unknown@example.test',
            'phone' => '+14155550134',
        ]);

        $knownResponse->assertStatus(202)->assertExactJson($unknownResponse->json());
        Notification::assertSentOnDemandTimes(GuestAccessCodeNotification::class, 1);
    }

    public function test_guest_access_start_is_rate_limited_per_email_and_ip(): void
    {
        Notification::fake();
        $payload = [
            'name' => 'New Guest',
            'email' => 'guest@example.test',
        ];

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/guest/access', $payload)->assertStatus(202);
        }

        $this->postJson('/api/guest/access', $payload)
            ->assertTooManyRequests();

        Notification::assertSentOnDemandTimes(GuestAccessCodeNotification::class, 5);
    }

    private function sentCodeTo(string $email): string
    {
        $code = null;

        Notification::assertSentOnDemand(
            GuestAccessCodeNotification::class,
            function (
                GuestAccessCodeNotification $notification,
                array $channels,
                object $notifiable,
            ) use ($email, &$code): bool {
                $code = $notification->code;

                return $channels === ['mail']
                    && $notifiable->routeNotificationFor('mail') === $email;
            },
        );

        $this->assertIsString($code);

        return $code;
    }
}
