<?php

namespace Tests\Feature\Api;

use App\Enums\SupportTicketStatus;
use App\Enums\UserRole;
use App\Models\Chapter;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_and_track_their_own_support_ticket(): void
    {
        $guest = User::factory()->create(['role' => UserRole::Guest]);
        $chapter = Chapter::factory()->create();
        Sanctum::actingAs($guest);

        $this->postJson('/api/support/tickets', [
            'chapter_id' => $chapter->id,
            'subject' => 'Unable to apply',
            'category' => 'membership',
            'message' => 'I need help with the application form.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.priority', 'normal')
            ->assertJsonPath('data.messages.0.message', 'I need help with the application form.')
            ->assertJsonPath('data.messages.0.is_internal', null);

        $ticket = SupportTicket::query()->firstOrFail();
        $this->getJson('/api/support/tickets')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ticket->id);
        $this->getJson('/api/support/tickets/'.$ticket->id)
            ->assertOk()
            ->assertJsonPath('data.requester.id', $guest->id);
    }

    public function test_user_cannot_read_or_reply_to_another_users_ticket(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Guest]);
        $ticket = $this->makeTicket($owner);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Guest]));

        $this->getJson('/api/support/tickets/'.$ticket->id)->assertForbidden();
        $this->postJson('/api/support/tickets/'.$ticket->id.'/messages', [
            'message' => 'Unauthorized reply.',
        ])->assertForbidden();
    }

    public function test_sub_admin_only_sees_assigned_chapter_tickets_and_can_escalate(): void
    {
        $assignedChapter = Chapter::factory()->create();
        $otherChapter = Chapter::factory()->create();
        $assignedTicket = $this->makeTicket(chapter: $assignedChapter);
        $otherTicket = $this->makeTicket(chapter: $otherChapter);
        $subAdmin = User::factory()->create(['role' => UserRole::SubAdmin]);
        $subAdmin->administeredChapters()->attach($assignedChapter);
        Sanctum::actingAs($subAdmin);

        $this->getJson('/api/admin/support/tickets')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignedTicket->id);

        $this->patchJson('/api/admin/support/tickets/'.$assignedTicket->id, [
            'status' => 'escalated',
            'priority' => 'high',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'escalated')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.escalated_by', $subAdmin->id);

        $this->patchJson('/api/admin/support/tickets/'.$otherTicket->id, [
            'status' => 'in_progress',
        ])->assertForbidden();
    }

    public function test_internal_admin_messages_are_hidden_from_requester(): void
    {
        $requester = User::factory()->create(['role' => UserRole::Member]);
        $chapter = Chapter::factory()->create();
        $ticket = $this->makeTicket($requester, $chapter);
        $admin = User::factory()->create(['role' => UserRole::SubAdmin]);
        $admin->administeredChapters()->attach($chapter);
        Sanctum::actingAs($admin);

        $this->postJson('/api/support/tickets/'.$ticket->id.'/messages', [
            'message' => 'Private note for staff.',
            'is_internal' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.messages.0.is_internal', false)
            ->assertJsonPath('data.messages.1.is_internal', true);

        Sanctum::actingAs($requester);
        $this->getJson('/api/support/tickets/'.$ticket->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.messages')
            ->assertJsonMissing(['message' => 'Private note for staff.']);

        $this->postJson('/api/support/tickets/'.$ticket->id.'/messages', [
            'message' => 'I can not create internal notes.',
            'is_internal' => true,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_internal');
    }

    public function test_resolved_ticket_cannot_be_reopened_or_replied_to(): void
    {
        $ticket = $this->makeTicket();
        $ticket->update(['status' => SupportTicketStatus::Resolved]);
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        Sanctum::actingAs($admin);

        $this->patchJson('/api/admin/support/tickets/'.$ticket->id, [
            'status' => 'in_progress',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        Sanctum::actingAs($ticket->requester);
        $this->postJson('/api/support/tickets/'.$ticket->id.'/messages', [
            'message' => 'Please reopen.',
        ])->assertUnprocessable()->assertJsonValidationErrors('message');
    }

    public function test_admin_roles_cannot_create_support_tickets(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->postJson('/api/support/tickets', [
            'subject' => 'Admin ticket',
            'category' => 'general',
            'message' => 'not permitted',
        ])->assertForbidden();

        $this->getJson('/api/admin/support/tickets')->assertOk();
    }

    private function makeTicket(
        ?User $requester = null,
        ?Chapter $chapter = null,
    ): SupportTicket {
        $requester ??= User::factory()->create(['role' => UserRole::Guest]);

        $ticket = SupportTicket::query()->create([
            'requester_id' => $requester->id,
            'chapter_id' => $chapter?->id,
            'subject' => 'Help needed',
            'category' => 'membership',
            'status' => SupportTicketStatus::Open,
        ]);
        $ticket->messages()->create([
            'user_id' => $requester->id,
            'body' => 'Initial help request.',
            'is_internal' => false,
        ]);

        return $ticket;
    }
}
