<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Challenge;
use App\Models\Spotting;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Spotting> */
class SpottingFactory extends Factory
{
    protected $model = Spotting::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'challenge_id' => Challenge::factory(),
            'campaign' => null,
            'notes' => fake()->sentence(),
            'owner' => fake()->firstName(),
            'picture' => '/uploads/example.jpg',
            'exif' => null,
            'latitude' => (string) fake()->latitude(51.7, 51.8),
            'longitude' => (string) fake()->longitude(-2.3, -2.1),
            'location' => 'Stroud',
            'what3words' => null,
            'gallery' => true,
            'map' => true,
            'merit' => false,
            'featured' => false,
            'likes' => 0,
            'comment' => null,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['featured' => true]);
    }
}
