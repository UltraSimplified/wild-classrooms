<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Challenge;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Challenge> */
class ChallengeFactory extends Factory
{
    protected $model = Challenge::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $species = fake()->randomElement(['Robin', 'Oak', 'Bluebell', 'Ladybird', 'Hedgehog']);

        return [
            'category_id' => Category::factory(),
            'month' => fake()->numberBetween(1, 12),
            'title' => $species,
            'plural' => $species.'s',
            'image_name' => null,
            'image' => null,
            'credit' => null,
            'teaser' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'where_to_find_them' => fake()->sentence(),
        ];
    }
}
