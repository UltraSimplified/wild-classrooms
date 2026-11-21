<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Spotting;
use App\Support\MediaSize;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maize\Markable\Models\Like;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

class GalleryController extends Controller
{
    private const SHOWN = 50;

    /** Session key for the spottings a guest has liked this visit. */
    private const GUEST_LIKES = 'gallery.liked';

    public function index(): Response
    {
        $guestLikes = request()->user() === null ? $this->guestLikes() : [];

        return Inertia::render('Gallery/Index', [
            'spottings' => $this->chosen($this->seed())
                ->map(fn (Spotting $spotting): array => [
                    'id' => $spotting->getKey(),
                    'picture' => MediaUrl::resolve($spotting->picture),
                    'size' => MediaSize::of($spotting->picture),
                    'notes' => $spotting->notes,
                    'comment' => $spotting->comment,
                    'challenge' => $spotting->challenge?->title,
                    'challengeId' => $spotting->challenge_id,
                    'group' => $spotting->unit?->group?->title,
                    // Signed-in likes live in markable_likes; the integer column holds the
                    // anonymous ones the old site counted.
                    'likes' => (int) $spotting->markable_likes_count + (int) $spotting->likes,
                    'liked' => (bool) $spotting->liked_by_viewer
                        || in_array((int) $spotting->getKey(), $guestLikes, true),
                ])->values()->all(),
        ]);
    }

    /**
     * Like a spotting.
     *
     * This was a GET route that mutated state and returned `true`, so it was
     * exempt from CSRF and any crawler following links inflated the counts.
     * It is a POST now, and liking toggles for everyone: a second press
     * takes the like back.
     *
     * A signed-in user's like is a row recorded against them. A guest's is
     * the anonymous count, with the photos they have liked remembered in
     * their session, so their hearts fill and pressing again takes the like
     * back rather than adding another. That lasts as long as the session
     * does, and clearing cookies starts afresh: it stops a child's repeated
     * taps, not someone determined to inflate a count.
     */
    public function store(Spotting $spotting): RedirectResponse
    {
        $user = request()->user();

        if ($user !== null) {
            Like::toggle($spotting, $user);
        } else {
            $this->toggleGuestLike($spotting);
        }

        // Back to the same photos in the same order; see seed().
        session()->flash('gallery.keep', true);

        return back();
    }

    private function toggleGuestLike(Spotting $spotting): void
    {
        $id = (int) $spotting->getKey();
        $liked = $this->guestLikes();

        if (in_array($id, $liked, true)) {
            // Never below zero, whatever the count was when the session began.
            Spotting::query()->whereKey($id)->where('likes', '>', 0)->decrement('likes');
            $liked = array_values(array_diff($liked, [$id]));
        } else {
            $spotting->increment('likes');
            $liked[] = $id;
        }

        session([self::GUEST_LIKES => $liked]);
    }

    /**
     * @return list<int>
     */
    private function guestLikes(): array
    {
        $liked = session(self::GUEST_LIKES, []);

        return is_array($liked) ? array_values(array_map('intval', $liked)) : [];
    }

    /**
     * The seed for this visit's order.
     *
     * A like redirects back here, and the page is rebuilt from scratch. With
     * a fresh shuffle each time, liking one photo dealt a different fifty in
     * a different order, so the photo just liked could vanish from under the
     * pointer. So a like asks for the last order again, and every other visit
     * still gets a new one. The old site went too far the other way: it
     * passed a fixed seed to inRandomOrder(), so everyone saw the same
     * "random" order, forever.
     */
    private function seed(): int
    {
        $last = session('gallery.seed');

        $seed = session('gallery.keep') === true && is_int($last)
            ? $last
            : random_int(1, PHP_INT_MAX);

        session(['gallery.seed' => $seed]);

        return $seed;
    }

    /**
     * Fifty gallery spottings, shuffled by the seed.
     *
     * The shuffle is done here rather than by the database, whose seeded
     * random functions differ between MySQL and SQLite (SQLite ignores the
     * seed), so the same seed deals the same photos everywhere. The database
     * then returns them in that order through a CASE, which both understand.
     *
     * @return Collection<int, Spotting>
     */
    private function chosen(int $seed): Collection
    {
        $ids = Spotting::query()
            ->where('gallery', true)
            ->whereNotNull('picture')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $chosen = array_slice(
            (new Randomizer(new Xoshiro256StarStar($seed)))->shuffleArray($ids),
            0,
            self::SHOWN,
        );

        if ($chosen === []) {
            return new Collection;
        }

        $positions = [];

        foreach ($chosen as $position => $id) {
            array_push($positions, $id, $position);
        }

        $viewer = request()->user();

        return Spotting::query()
            ->with(['challenge', 'unit.group'])
            ->withCount('markableLikes')
            ->when($viewer !== null, fn ($query) => $query->withExists([
                'markableLikes as liked_by_viewer' => fn ($likes) => $likes->where('user_id', $viewer?->getKey()),
            ]))
            ->whereKey($chosen)
            ->orderByRaw(
                'CASE spottings.id '.str_repeat('WHEN ? THEN ? ', count($chosen)).'END',
                $positions,
            )
            ->get();
    }
}
