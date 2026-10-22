<?php

namespace Tests\Feature\Api;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicGuestWorkflowControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_event_listing_only_returns_published_upcoming_events(): void
    {
        $publicEvent = $this->makeEvent(['title' => 'Upcoming event']);
        $this->makeEvent(['status' => EventStatus::Draft]);
        $this->makeEvent(['starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(2)]);

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $publicEvent->id)
            ->assertJsonPath('data.0.title', 'Upcoming event');
    }

    public function test_guest_can_register_and_duplicate_email_is_rejected(): void
    {
        $event = $this->makeEvent();
        $payload = [
            'name' => 'Guest User',
            'email' => '  PERSON@EXAMPLE.COM ',
            'phone' => '+1 (415) 555-0100',
            'business_name' => 'Northwind',
        ];

        $this->postJson('/api/events/'.$event->id.'/registrations', $payload)
            ->assertCreated()
            ->assertJsonPath('message', 'Your event registration has been received.');

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'email' => 'person@example.com',
            'phone' => '+14155550100',
            'user_id' => null,
            'status' => EventRegistrationStatus::Registered->value,
        ]);

        $this->postJson('/api/events/'.$event->id.'/registrations', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_requires_open_published_event_and_available_capacity(): void
    {
        $draft = $this->makeEvent(['status' => EventStatus::Draft]);
        $full = $this->makeEvent(['capacity' => 1]);
        $this->register($full, 'first@example.com')->assertCreated();

        $payload = [
            'name' => 'Second Guest',
            'email' => 'second@example.com',
        ];

        $this->postJson('/api/events/'.$draft->id.'/registrations', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('event');
        $this->postJson('/api/events/'.$full->id.'/registrations', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('event');

        $closed = $this->makeEvent(['registration_closes_at' => now()->subMinute()]);
        $this->postJson('/api/events/'.$closed->id.'/registrations', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('event');
    }

    public function test_verified_signed_in_identity_is_linked_but_unverified_email_is_not(): void
    {
        $verifiedUser = User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
        ]);
        $unverifiedUser = User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
        ]);
        $event = $this->makeEvent();

        Sanctum::actingAs($verifiedUser);
        $this->register($event, 'verified@example.com')->assertCreated();

        Sanctum::actingAs($unverifiedUser);
        $this->register($event, 'unverified@example.com')->assertCreated();

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'email' => 'verified@example.com',
            'user_id' => $verifiedUser->id,
        ]);
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'email' => 'unverified@example.com',
            'user_id' => null,
        ]);
    }

    public function test_public_contact_inquiry_is_normalized_and_not_returned_to_submitter(): void
    {
        $this->postJson('/api/contact-inquiries', [
            'name' => 'Guest User',
            'email' => ' PERSON@EXAMPLE.COM ',
            'phone' => '+1 (415) 555-0100',
            'subject' => 'Membership information',
            'message' => 'Please contact me about membership.',
        ])
            ->assertAccepted()
            ->assertJsonMissingPath('data.email');

        $this->assertDatabaseHas('contact_inquiries', [
            'email' => 'person@example.com',
            'phone' => '+14155550100',
            'status' => 'new',
            'user_id' => null,
        ]);
    }

    public function test_contact_inquiry_links_only_matching_verified_signed_in_identity(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/contact-inquiries', [
            'name' => 'Member',
            'email' => 'MEMBER@example.com',
            'subject' => 'Help',
            'message' => 'I need help.',
        ])->assertAccepted();

        $this->assertDatabaseHas('contact_inquiries', [
            'email' => 'member@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_contact_inquiry_fields_are_validated(): void
    {
        $this->postJson('/api/contact-inquiries', [
            'name' => '',
            'email' => 'invalid',
            'subject' => '',
            'message' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);
    }

    public function test_feedback_requires_an_attended_verified_registration(): void
    {
        $event = $this->makeEvent([
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDays(2)->addHours(2),
        ]);
        $user = User::factory()->create([
            'role' => UserRole::Guest,
            'email' => 'attendee@example.com',
            'email_verified_at' => now(),
        ]);
        EventRegistration::query()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'name' => 'Attendee',
            'email' => $user->email,
            'status' => EventRegistrationStatus::Registered,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/events/'.$event->id.'/feedback', [
            'rating' => 5,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('event');

        $this->postJson('/api/events/'.$event->id.'/feedback', [
            'rating' => 6,
        ])->assertUnprocessable()->assertJsonValidationErrors('rating');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeEvent(array $attributes = []): Event
    {
        return Event::query()->create(array_merge([
            'title' => 'Networking evening',
            'description' => 'Meet local business owners.',
            'venue' => 'Community hall',
            'starts_at' => now()->addDays(5),
            'ends_at' => now()->addDays(5)->addHours(2),
            'status' => EventStatus::Published,
        ], $attributes));
    }

    private function register(Event $event, string $email)
    {
        return $this->postJson('/api/events/'.$event->id.'/registrations', [
            'name' => 'Guest User',
            'email' => $email,
        ]);
    }
}
