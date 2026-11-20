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
        // Any one species is fine on its own; the seeder overrides the title
        // so a seeded year has 60 distinct ones, as production does.
        $species = fake()->randomElement([
            'Robin', 'Blackbird', 'Wren', 'Blue Tit', 'Goldfinch', 'Starling',
            'Oak', 'Ash', 'Beech', 'Silver Birch', 'Hazel', 'Rowan',
            'Bluebell', 'Cow Parsley', 'Snowdrop', 'Foxglove', 'Primrose', 'Dog Rose',
            'Ladybird', 'Woodlouse', 'Earthworm', 'Garden Snail', 'Bumblebee', 'Centipede',
            'Peacock Butterfly', 'Small Copper', 'Red Admiral', 'Brimstone',
            'Orange Tip', 'Elephant Hawk-moth', 'Hedgehog', 'Grey Squirrel',
            'Field Vole', 'Roe Deer', 'Badger', 'Fox', 'Frog', 'Newt',
            'Dogwood', 'Elder', 'Holly', 'Ivy', 'Hawthorn', 'Blackthorn',
            'Nettle', 'Dandelion', 'Daisy', 'Buttercup', 'Clover', 'Yarrow',
            'Song Thrush', 'Chaffinch', 'Magpie', 'Jackdaw', 'Nuthatch', 'Treecreeper',
            'Speckled Wood', 'Comma', 'Six-spot Burnet', 'Cinnabar Moth',
        ]);

        return [
            'category_id' => Category::factory(),
            'month' => fake()->numberBetween(1, 12),
            'title' => $species,
            'plural' => $species.'s',
            'image_name' => null,
            'image' => self::storageImage(),
            'credit' => null,
            'teaser' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'where_to_find_them' => fake()->sentence(),
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
