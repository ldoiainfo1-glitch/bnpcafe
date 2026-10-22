<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChapterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sub_admin_only_receives_assigned_chapters(): void
    {
        $subAdmin = User::factory()->create(['role' => UserRole::SubAdmin]);
        $assignedChapter = Chapter::factory()->create(['name' => 'Assigned Chapter']);
        Chapter::factory()->create(['name' => 'Other Chapter']);
        $subAdmin->administeredChapters()->attach($assignedChapter);

        Sanctum::actingAs($subAdmin);

        $this->getJson('/api/admin/chapters')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignedChapter->id);
    }

    public function test_sub_admin_cannot_learn_about_an_unassigned_chapter(): void
    {
        $subAdmin = User::factory()->create(['role' => UserRole::SubAdmin]);
        $chapter = Chapter::factory()->create();

        Sanctum::actingAs($subAdmin);

        $this->getJson('/api/admin/chapters/'.$chapter->id)
            ->assertNotFound();
    }

    public function test_super_admin_can_view_chapters_across_the_platform(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        Chapter::factory()->create();
        $secondChapter = Chapter::factory()->create();

        Sanctum::actingAs($superAdmin);

        $this->getJson('/api/admin/chapters')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/admin/chapters/'.$secondChapter->id)
            ->assertOk()
            ->assertJsonPath('data.id', $secondChapter->id);
    }

    public function test_super_admin_can_create_and_update_chapters(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->postJson('/api/admin/chapters', [
            'pincode' => '10101',
            'name' => 'North Chapter',
            'state' => 'North State',
            'is_active' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'North Chapter');

        $chapter = Chapter::query()->where('pincode', '10101')->firstOrFail();

        $this->patchJson('/api/admin/chapters/'.$chapter->id, [
            'name' => 'Updated North Chapter',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated North Chapter')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_sub_admin_cannot_create_or_update_chapters(): void
    {
        $chapter = Chapter::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SubAdmin]));

        $this->postJson('/api/admin/chapters', [
            'pincode' => '20202',
            'name' => 'Restricted Chapter',
        ])->assertForbidden();

        $this->patchJson('/api/admin/chapters/'.$chapter->id, [
            'name' => 'Unauthorized update',
        ])->assertForbidden();
    }

    public function test_chapter_updates_reject_duplicate_pincodes(): void
    {
        $chapter = Chapter::factory()->create(['pincode' => '30303']);
        Chapter::factory()->create(['pincode' => '40404']);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->patchJson('/api/admin/chapters/'.$chapter->id, [
            'pincode' => '40404',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('pincode');
    }

    public function test_unauthenticated_requests_cannot_view_admin_chapters(): void
    {
        $this->getJson('/api/admin/chapters')->assertUnauthorized();
    }

    #[DataProvider('nonAdminRoles')]
    public function test_member_and_guest_cannot_list_admin_chapters(UserRole $role): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => $role]));

        $this->getJson('/api/admin/chapters')->assertForbidden();
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function nonAdminRoles(): array
    {
        return [
            'member' => [UserRole::Member],
            'guest' => [UserRole::Guest],
        ];
    }
}
