<?php

namespace Tests\Feature\Api;

use App\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicChapterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_chapter_list_contains_only_active_chapters_and_safe_fields(): void
    {
        $activeChapter = Chapter::factory()->create([
            'name' => 'Metro Chapter',
            'state' => 'West',
            'chapter_head_email' => 'private@example.test',
            'chapter_head_phone' => '+14155550100',
            'postal_address' => 'Private office address',
        ]);
        Chapter::factory()->create(['is_active' => false]);

        $this->getJson('/api/chapters')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeChapter->id)
            ->assertJsonPath('data.0.name', 'Metro Chapter')
            ->assertJsonMissingPath('data.0.chapter_head_email')
            ->assertJsonMissingPath('data.0.chapter_head_phone')
            ->assertJsonMissingPath('data.0.postal_address');
    }
}
