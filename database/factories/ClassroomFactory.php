<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Classroom> */
class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'teacher' => fake()->name(),
            'year' => (string) fake()->numberBetween(1, 6),
            'key_stage' => (string) fake()->numberBetween(1, 2),
            'intake_year' => (string) fake()->numberBetween(2018, 2026),
            'school_id' => School::factory(),
        ];
    }
}
