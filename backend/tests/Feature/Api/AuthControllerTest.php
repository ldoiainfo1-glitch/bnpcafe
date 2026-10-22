<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_authenticates_user_and_returns_safe_user_data(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.test',
            'password' => Hash::make('correct-password'),
            'role' => UserRole::Member,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'member@example.test',
            'password' => 'correct-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', UserRole::Member->value)
            ->assertJsonPath('data.password_set', true)
            ->assertJsonMissingPath('data.password_set_at')
            ->assertJsonMissing(['password' => $user->password]);

        $this->assertNotNull($user->fresh()->password_set_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_set_a_password_after_verified_email_sign_in(): void
    {
        $user = User::factory()->create([
            'password_set_at' => null,
        ]);

        $this->actingAs($user)
            ->patchJson('/api/auth/password', [
                'password' => 'a-secure-new-password',
                'password_confirmation' => 'a-secure-new-password',
            ])
            ->assertNoContent();

        $user->refresh();
        $this->assertTrue(Hash::check('a-secure-new-password', $user->password));
        $this->assertNotNull($user->password_set_at);
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'current-secure-password',
            'password_set_at' => now(),
        ]);

        $this->actingAs($user)
            ->patchJson('/api/auth/password', [
                'current_password' => 'incorrect-current-password',
                'password' => 'a-different-secure-password',
                'password_confirmation' => 'a-different-secure-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->patchJson('/api/auth/password', [
            'current_password' => 'current-secure-password',
            'password' => 'a-different-secure-password',
            'password_confirmation' => 'a-different-secure-password',
        ])->assertNoContent();

        $this->assertTrue(Hash::check('a-different-secure-password', $user->fresh()->password));
    }

    public function test_password_setup_and_change_require_authentication(): void
    {
        $this->patchJson('/api/auth/password', [
            'password' => 'a-secure-new-password',
            'password_confirmation' => 'a-secure-new-password',
        ])->assertUnauthorized();
    }

    public function test_login_returns_generic_validation_error_for_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'member@example.test',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'member@example.test',
            'password' => 'incorrect-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.test',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'member@example.test',
            'password' => 'correct-password',
        ])->assertOk();

        $this->postJson('/api/auth/logout')
            ->assertNoContent();

        $this->assertGuest();
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_first_party_spa_session_authenticates_api_requests(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.test',
            'password' => Hash::make('correct-password'),
            'role' => UserRole::Member,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'member@example.test',
            'password' => 'correct-password',
        ])->assertOk();

        $this->withHeader('Origin', 'http://localhost:5173')
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', UserRole::Member->value);
    }

    public function test_cors_allows_the_configured_spa_origin_with_credentials(): void
    {
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type,x-xsrf-token',
        ])->options('/api/auth/login')
            ->assertNoContent()
            ->assertHeader('access-control-allow-origin', 'http://localhost:5173')
            ->assertHeader('access-control-allow-credentials', 'true');
    }

    public function test_login_is_rate_limited_by_email_and_ip(): void
    {
        $credentials = [
            'email' => 'missing@example.test',
            'password' => 'incorrect-password',
        ];

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/auth/login', $credentials)->assertUnprocessable();
        }

        $this->postJson('/api/auth/login', $credentials)
            ->assertTooManyRequests();
    }

    public function test_login_is_rate_limited_across_email_addresses_from_one_ip(): void
    {
        foreach (range(1, 30) as $attempt) {
            $this->postJson('/api/auth/login', [
                'email' => "missing-{$attempt}@example.test",
                'password' => 'incorrect-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/auth/login', [
            'email' => 'another-missing@example.test',
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }
}
