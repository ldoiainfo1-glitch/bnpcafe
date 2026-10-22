<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Chapter;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentityAndChapterFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_default_to_the_guest_role(): void
    {
        $user = User::factory()->create()->refresh();

        $this->assertSame(UserRole::Guest, $user->role);
    }

    public function test_sub_admins_can_be_assigned_to_chapters(): void
    {
        $subAdmin = User::factory()->create();
        $subAdmin->forceFill(['role' => UserRole::SubAdmin])->save();
        $chapter = Chapter::create([
            'pincode' => '400001',
            'name' => 'Mumbai Central',
        ]);

        $subAdmin->administeredChapters()->attach($chapter);

        $this->assertTrue($subAdmin->administeredChapters->contains($chapter));
        $this->assertTrue($chapter->adminUsers->contains($subAdmin));
    }

    public function test_an_identity_value_cannot_be_linked_to_two_users(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        UserIdentity::create([
            'user_id' => $firstUser->id,
            'type' => 'phone',
            'normalized_value' => '+14155550100',
        ]);

        $this->expectException(QueryException::class);

        UserIdentity::create([
            'user_id' => $secondUser->id,
            'type' => 'phone',
            'normalized_value' => '+14155550100',
        ]);
    }
}
