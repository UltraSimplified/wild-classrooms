<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Post> */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'title' => fake()->sentence(4),
            'intro' => fake()->sentence(),
            'post_date' => fake()->date(),
            'content' => fake()->paragraphs(3, true),
            'category_id' => Category::factory(),
            'challenge_id' => null,
            'featured_image' => self::storageImage() ?? '',
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
