<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\MediaUrl;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Dig Deeper" — the project's blog.
 */
class PostController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Posts/Index', [
            'posts' => Post::query()
                ->with(['category', 'author'])
                ->orderByDesc('post_date')
                ->get()
                ->map(fn (Post $post): array => [
                    'id' => $post->getKey(),
                    'title' => $post->title,
                    'slug' => str((string) $post->title)->slug()->value(),
                    'intro' => $post->intro,
                    'image' => MediaUrl::resolve($post->featured_image),
                    'postedAt' => $post->post_date->format('j M Y'),
                    'category' => $post->category?->title,
                    'author' => $post->author?->name,
                ])->all(),
        ]);
    }

    public function show(Post $post): Response
    {
        $post->load(['category', 'author', 'challenge']);

        return Inertia::render('Posts/Show', [
            'post' => [
                'id' => $post->getKey(),
                'title' => $post->title,
                'intro' => $post->intro,
                'content' => $post->content,
                'image' => MediaUrl::resolve($post->featured_image),
                'postedAt' => $post->post_date->format('j M Y'),
                'category' => $post->category?->title,
                'author' => $post->author?->name,
                'challenge' => $post->challenge === null ? null : [
                    'id' => $post->challenge->getKey(),
                    'title' => $post->challenge->title,
                    'slug' => str((string) $post->challenge->title)->slug()->value(),
                ],
            ],
        ]);
    }
}
