<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<School> */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'school' => fake()->company().' Primary School',
            'logo' => '/uploads/logo.png',
            'featured_image' => '/uploads/school.jpg',
            'statement' => fake()->paragraph(),
            'head' => fake()->name(),
            'address_line_1' => fake()->streetAddress(),
            'city' => 'Stroud',
            'postal_code' => 'GL5 1AF',
            'accent_colour' => '#21B978',
            'latitude' => (string) fake()->latitude(51.7, 51.8),
            'longitude' => (string) fake()->longitude(-2.3, -2.1),
        ];
    }
}
