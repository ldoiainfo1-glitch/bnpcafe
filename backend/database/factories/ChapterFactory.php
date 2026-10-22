<?php

namespace Database\Factories;

use App\Models\Chapter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chapter>
 */
class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pincode' => fake()->unique()->numerify('#####'),
            'name' => fake()->city().' Chapter',
            'state' => fake()->state(),
            'is_active' => true,
        ];
    }
}
