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
            'featured_image' => '/uploads/post.jpg',
        ];
    }
}
