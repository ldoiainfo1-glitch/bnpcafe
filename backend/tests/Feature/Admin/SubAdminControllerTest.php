<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_and_list_sub_admins_with_chapter_assignments(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $chapter = Chapter::factory()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/sub-admins', [
            'name' => 'Chapter Admin',
            'email' => ' CHAPTER.ADMIN@EXAMPLE.TEST ',
            'chapter_ids' => [$chapter->id],
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Chapter Admin')
            ->assertJsonPath('data.email', 'chapter.admin@example.test')
            ->assertJsonPath('data.role', 'sub_admin')
            ->assertJsonPath('data.chapters.0.id', $chapter->id);

        $subAdmin = User::query()->where('email', 'chapter.admin@example.test')->firstOrFail();
        $this->assertSame(UserRole::SubAdmin, $subAdmin->role);
        $this->assertNull($subAdmin->password_set_at);
        $this->assertDatabaseHas('chapter_admin_assignments', [
            'user_id' => $subAdmin->id,
            'chapter_id' => $chapter->id,
        ]);

        $this->getJson('/api/admin/sub-admins')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $subAdmin->id);
    }

    public function test_super_admin_can_replace_sub_admin_chapter_assignments(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $subAdmin = User::factory()->create(['role' => UserRole::SubAdmin]);
        $oldChapter = Chapter::factory()->create();
        $newChapter = Chapter::factory()->create();
        $subAdmin->administeredChapters()->attach($oldChapter);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/sub-admins/{$subAdmin->id}/chapters", [
            'chapter_ids' => [$newChapter->id],
        ])
            ->assertOk()
            ->assertJsonPath('data.chapters.0.id', $newChapter->id);

        $this->assertDatabaseMissing('chapter_admin_assignments', [
            'user_id' => $subAdmin->id,
            'chapter_id' => $oldChapter->id,
        ]);
        $this->assertDatabaseHas('chapter_admin_assignments', [
            'user_id' => $subAdmin->id,
            'chapter_id' => $newChapter->id,
        ]);
    }

    public function test_only_super_admins_can_manage_sub_admins(): void
    {
        $chapter = Chapter::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SubAdmin]));

        $this->getJson('/api/admin/sub-admins')->assertForbidden();
        $this->postJson('/api/admin/sub-admins', [
            'name' => 'Not Authorized',
            'email' => 'not-authorized@example.test',
            'chapter_ids' => [$chapter->id],
        ])->assertForbidden();
    }

    public function test_sub_admin_assignments_must_reference_active_chapters(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $inactiveChapter = Chapter::factory()->create(['is_active' => false]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/sub-admins', [
            'name' => 'Chapter Admin',
            'email' => 'chapter.admin@example.test',
            'chapter_ids' => [$inactiveChapter->id],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('chapter_ids.0');
    }
}
