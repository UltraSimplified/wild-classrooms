<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Group> */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'title' => fake()->words(2, true).' Group',
            'type' => fake()->randomElement(['Group', 'Family', 'School', 'Other']),
            'description' => fake()->sentence(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => null,
            'city' => 'Stroud',
            'county' => 'Gloucestershire',
            'postal_code' => 'GL5 1AF',
            'latitude' => (string) fake()->latitude(51.7, 51.8),
            'longitude' => (string) fake()->longitude(-2.3, -2.1),
            'public' => '1',
            'image' => self::storageImage(),
        ];
    }

    /**
     * A real image from public/storage when a copy of the production files is
     * present, stored the way Nova stores them: a bare filename on the public
     * disk. Returns null when there is no copy, so cards render empty rather
     * than broken.
     */
    private static function storageImage(): ?string
    {
        static $files = null;

        $files ??= array_values(array_map(
            fn (string $path): string => basename($path),
            array_filter(
                glob(public_path('storage').'/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [],
                fn (string $path): bool => filesize($path) > 0,
            ),
        ));

        return $files === [] ? null : $files[array_rand($files)];
    }
}
