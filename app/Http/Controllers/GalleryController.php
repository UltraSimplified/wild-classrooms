<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Spotting;
use App\Support\MediaUrl;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maize\Markable\Models\Like;

class GalleryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Gallery/Index', [
            'spottings' => Spotting::query()
                ->with(['challenge', 'unit.group'])
                ->withCount('markableLikes')
                ->where('gallery', true)
                ->whereNotNull('picture')
                // The old query passed a fixed seed to inRandomOrder(), so the
                // "random" gallery showed the same order to everyone, forever.
                ->inRandomOrder()
                ->limit(50)
                ->get()
                ->map(fn (Spotting $spotting): array => [
                    'id' => $spotting->getKey(),
                    'picture' => MediaUrl::resolve($spotting->picture),
                    'notes' => $spotting->notes,
                    'comment' => $spotting->comment,
                    'challenge' => $spotting->challenge?->title,
                    'challengeId' => $spotting->challenge_id,
                    'group' => $spotting->unit?->group?->title,
                    // Signed-in likes live in markable_likes; the integer column holds the
                    // anonymous ones the old site counted.
                    'likes' => (int) $spotting->markable_likes_count + (int) $spotting->likes,
                ])->all(),
        ]);
    }

    /**
     * Like a spotting.
     *
     * This was a GET route that mutated state and returned `true`, so it was
     * exempt from CSRF and any crawler following links inflated the counts.
     * It is a POST now, and a signed-in user's like is recorded against them
     * so it cannot be repeated.
     */
    public function store(Spotting $spotting): RedirectResponse
    {
        $user = request()->user();

        if ($user !== null) {
            Like::toggle($spotting, $user);
        } else {
            $spotting->increment('likes');
        }

        return back();
    }
}
