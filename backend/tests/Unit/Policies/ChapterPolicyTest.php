<?php

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChapterPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_any_chapter(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $chapter = Chapter::factory()->create();

        $this->assertTrue(Gate::forUser($superAdmin)->allows('viewAny', Chapter::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $chapter));
    }

    public function test_sub_admin_can_view_only_assigned_chapters(): void
    {
        $subAdmin = User::factory()->create(['role' => UserRole::SubAdmin]);
        $assignedChapter = Chapter::factory()->create();
        $unassignedChapter = Chapter::factory()->create();
        $subAdmin->administeredChapters()->attach($assignedChapter);

        $this->assertTrue(Gate::forUser($subAdmin)->allows('viewAny', Chapter::class));
        $this->assertTrue(Gate::forUser($subAdmin)->allows('view', $assignedChapter));
        $this->assertFalse(Gate::forUser($subAdmin)->allows('view', $unassignedChapter));
    }

    #[DataProvider('nonAdminRoles')]
    public function test_member_and_guest_cannot_view_admin_chapters(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $chapter = Chapter::factory()->create();

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Chapter::class));
        $this->assertFalse(Gate::forUser($user)->allows('view', $chapter));
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
