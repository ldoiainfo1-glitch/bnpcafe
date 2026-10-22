<?php

namespace Tests\Feature\Admin;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Chapter;
use App\Models\ContactInquiry;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventAndInquiryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_and_update_events(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->postJson('/api/admin/events', [
            'title' => 'Business breakfast',
            'venue' => 'BNP Cafe',
            'starts_at' => now()->addDays(4)->toISOString(),
            'ends_at' => now()->addDays(4)->addHours(2)->toISOString(),
            'status' => 'published',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'published');

        $event = Event::query()->firstOrFail();

        $this->patchJson('/api/admin/events/'.$event->id, [
            'title' => 'Updated breakfast',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated breakfast');
    }

    public function test_event_date_ranges_are_validated_on_create_and_partial_update(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $start = now()->addDays(4);

        $this->postJson('/api/admin/events', [
            'title' => 'Invalid',
            'venue' => 'BNP Cafe',
            'starts_at' => $start->toISOString(),
            'ends_at' => $start->copy()->subHour()->toISOString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ends_at');

        $event = $this->makeEvent();
        $this->patchJson('/api/admin/events/'.$event->id, [
            'starts_at' => now()->addDays(8)->toISOString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ends_at');
    }

    public function test_sub_admin_can_view_registrations_only_for_assigned_chapter_events(): void
    {
        $assignedChapter = Chapter::factory()->create();
        $otherChapter = Chapter::factory()->create();
        $assignedEvent = $this->makeEvent(['chapter_id' => $assignedChapter->id]);
        $otherEvent = $this->makeEvent(['chapter_id' => $otherChapter->id]);
        $registration = EventRegistration::query()->create([
            'event_id' => $assignedEvent->id,
            'name' => 'Guest',
            'email' => 'guest@example.com',
        ]);
        EventRegistration::query()->create([
            'event_id' => $otherEvent->id,
            'name' => 'Other Guest',
            'email' => 'other@example.com',
        ]);
        $subAdmin = User::factory()->create(['role' => UserRole::SubAdmin]);
        $subAdmin->administeredChapters()->attach($assignedChapter);
        Sanctum::actingAs($subAdmin);

        $this->getJson('/api/admin/events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignedEvent->id);
        $this->getJson('/api/admin/events/'.$assignedEvent->id.'/registrations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $registration->id);
        $this->getJson('/api/admin/events/'.$otherEvent->id.'/registrations')
            ->assertForbidden();
    }

    public function test_only_super_admin_can_manage_events_or_contact_inquiries(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SubAdmin]));

        $this->postJson('/api/admin/events', [])->assertForbidden();
        $this->getJson('/api/admin/contact-inquiries')->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $inquiry = ContactInquiry::query()->create([
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'subject' => 'Question',
            'message' => 'Please contact me.',
        ]);

        $this->getJson('/api/admin/contact-inquiries')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inquiry->id);
    }

    public function test_super_admin_can_process_and_resolve_contact_inquiries(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $inquiry = ContactInquiry::query()->create([
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'subject' => 'Question',
            'message' => 'Please contact me.',
        ]);
        Sanctum::actingAs($admin);

        $this->patchJson('/api/admin/contact-inquiries/'.$inquiry->id, [
            'status' => 'in_progress',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->patchJson('/api/admin/contact-inquiries/'.$inquiry->id, [
            'status' => 'resolved',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved')
            ->assertJsonPath('data.resolved_by', $admin->id);

        $this->patchJson('/api/admin/contact-inquiries/'.$inquiry->id, [
            'status' => 'in_progress',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_member_cannot_view_admin_event_registration_or_inquiry_data(): void
    {
        $event = $this->makeEvent();
        $inquiry = ContactInquiry::query()->create([
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'subject' => 'Question',
            'message' => 'Please contact me.',
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Member]));

        $this->getJson('/api/admin/events')->assertForbidden();
        $this->getJson('/api/admin/events/'.$event->id.'/registrations')->assertForbidden();
        $this->getJson('/api/admin/contact-inquiries')->assertForbidden();
        $this->patchJson('/api/admin/contact-inquiries/'.$inquiry->id, [
            'status' => 'resolved',
        ])->assertForbidden();
    }

    public function test_assigned_sub_admin_marks_attendance_and_attendee_submits_feedback(): void
    {
        $chapter = Chapter::factory()->create();
        $event = $this->makeEvent([
            'chapter_id' => $chapter->id,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDays(2)->addHours(2),
        ]);
        $attendee = User::factory()->create([
            'role' => UserRole::Guest,
            'email' => 'attendee@example.com',
            'email_verified_at' => now(),
        ]);
        $registration = EventRegistration::query()->create([
            'event_id' => $event->id,
            'user_id' => $attendee->id,
            'name' => 'Attendee',
            'email' => $attendee->email,
            'status' => EventRegistrationStatus::Registered,
        ]);
        $subAdmin = User::factory()->create(['role' => UserRole::SubAdmin]);
        $subAdmin->administeredChapters()->attach($chapter);
        Sanctum::actingAs($subAdmin);

        $this->patchJson('/api/admin/events/'.$event->id.'/registrations/'.$registration->id.'/attendance', [
            'attended' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'registered')
            ->assertJsonPath('data.attended_at', fn ($value) => is_string($value));

        $this->getJson('/api/admin/events/'.$event->id.'/feedback')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        Sanctum::actingAs($attendee);
        $this->postJson('/api/events/'.$event->id.'/feedback', [
            'rating' => 5,
            'comment' => 'Excellent event.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Excellent event.');

        $this->postJson('/api/events/'.$event->id.'/feedback', [
            'rating' => 4,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('event');

        Sanctum::actingAs($subAdmin);
        $this->getJson('/api/admin/events/'.$event->id.'/feedback')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 5);
    }

    public function test_attendance_cannot_be_marked_for_unstarted_or_cancelled_events(): void
    {
        $chapter = Chapter::factory()->create();
        $event = $this->makeEvent([
            'chapter_id' => $chapter->id,
            'starts_at' => now()->addDay(),
        ]);
        $registration = EventRegistration::query()->create([
            'event_id' => $event->id,
            'name' => 'Guest',
            'email' => 'guest@example.com',
        ]);
        $subAdmin = User::factory()->create(['role' => UserRole::SubAdmin]);
        $subAdmin->administeredChapters()->attach($chapter);
        Sanctum::actingAs($subAdmin);

        $this->patchJson('/api/admin/events/'.$event->id.'/registrations/'.$registration->id.'/attendance', [
            'attended' => true,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attended');
    }

    private function makeEvent(array $attributes = []): Event
    {
        return Event::query()->create(array_merge([
            'title' => fake()->unique()->sentence(3),
            'venue' => 'BNP Cafe',
            'starts_at' => now()->addDays(4),
            'ends_at' => now()->addDays(4)->addHours(2),
            'status' => EventStatus::Published,
        ], $attributes));
    }
}
