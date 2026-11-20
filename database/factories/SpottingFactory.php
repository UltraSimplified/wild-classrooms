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

    /**
     * Real photo filenames from public/uploads, when a copy of the production
     * images is present. Seeding with a made-up path leaves every image in the
     * gallery broken, which looks like a bug in the port rather than missing
     * fixture data.
     *
     * @var list<string>|null
     */
    private static ?array $available = null;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'challenge_id' => Challenge::factory(),
            'campaign' => null,
            'notes' => fake()->sentence(),
            'owner' => fake()->firstName(),
            'picture' => self::picture(),
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

    /**
     * A real uploaded photo if one is on disk, otherwise none at all —
     * better an empty frame than a broken one.
     */
    private static function picture(): ?string
    {
        self::$available ??= array_values(array_map(
            fn (string $path): string => '/uploads/'.basename($path),
            array_filter(
                glob(public_path('uploads').'/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [],
                fn (string $path): bool => filesize($path) > 0,
            ),
        ));

        if (self::$available === []) {
            return null;
        }

        return self::$available[array_rand(self::$available)];
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['featured' => true]);
    }
}
