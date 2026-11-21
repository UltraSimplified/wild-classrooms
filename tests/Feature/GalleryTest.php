<?php

declare(strict_types=1);

use App\Models\Spotting;
use App\Models\User;
use Illuminate\Testing\TestResponse;

/*
 * The gallery shows fifty photos in a random order. A like redirects back to
 * it, which rebuilt the page with a fresh shuffle, so liking one photo dealt a
 * different fifty in a different order and the photo just liked could vanish.
 */

/**
 * @return list<int>
 */
function galleryIds(TestResponse $response): array
{
    return array_column(gallerySpottings($response), 'id');
}

/**
 * @return list<array{id: int, likes: int, liked: bool}>
 */
function gallerySpottings(TestResponse $response): array
{
    /** @var array{props: array{spottings: list<array{id: int, likes: int, liked: bool}>}} $page */
    $page = $response->viewData('page');

    return $page['props']['spottings'];
}

beforeEach(function (): void {
    // More than the fifty shown, so which photos are dealt matters too.
    Spotting::factory()->count(60)->create(['gallery' => true, 'picture' => '/uploads/a.jpg', 'likes' => 0]);
});

it('shows fifty photos', function (): void {
    expect(galleryIds($this->get('/gallery')))->toHaveCount(50);
});

it('keeps the same photos in the same order after a like', function (): void {
    $before = galleryIds($this->get('/gallery'));
    $liked = $before[7];

    $this->from('/gallery')->post("/gallery/{$liked}/like")->assertRedirect('/gallery');

    $after = $this->get('/gallery');

    expect(galleryIds($after))->toBe($before);

    /** @var array{props: array{spottings: list<array{id: int, likes: int}>}} $page */
    $page = $after->viewData('page');

    expect($page['props']['spottings'][7]['likes'])->toBe(1);
});

it('deals a fresh order on any other visit', function (): void {
    $first = galleryIds($this->get('/gallery'));

    $this->from('/gallery')->post("/gallery/{$first[0]}/like");
    $this->get('/gallery');

    // The like's request for the same order is used up by the page it lands on.
    expect(galleryIds($this->get('/gallery')))->not->toBe($first);
});

it('fills the heart on the photos the signed-in user has liked, and only those', function (): void {
    $user = User::factory()->create();
    $ids = galleryIds($this->actingAs($user)->get('/gallery'));

    $this->from('/gallery')->post("/gallery/{$ids[3]}/like");

    $liked = array_column(array_filter(
        gallerySpottings($this->get('/gallery')),
        fn (array $spotting): bool => $spotting['liked'],
    ), 'id');

    expect($liked)->toBe([$ids[3]]);
});

it('empties the heart again when they take the like back', function (): void {
    $user = User::factory()->create();
    $ids = galleryIds($this->actingAs($user)->get('/gallery'));

    $this->from('/gallery')->post("/gallery/{$ids[3]}/like");
    $this->get('/gallery');
    $this->from('/gallery')->post("/gallery/{$ids[3]}/like");

    $spotting = gallerySpottings($this->get('/gallery'))[3];

    expect($spotting['id'])->toBe($ids[3])
        ->and($spotting['liked'])->toBeFalse()
        ->and($spotting['likes'])->toBe(0);
});

it('fills the heart on a guest\'s like, and takes it back on a second press', function (): void {
    $ids = galleryIds($this->get('/gallery'));

    $this->from('/gallery')->post("/gallery/{$ids[3]}/like");
    $liked = gallerySpottings($this->get('/gallery'))[3];

    $this->from('/gallery')->post("/gallery/{$ids[3]}/like");
    $unliked = gallerySpottings($this->get('/gallery'))[3];

    expect($liked)->toMatchArray(['id' => $ids[3], 'likes' => 1, 'liked' => true])
        ->and($unliked)->toMatchArray(['id' => $ids[3], 'likes' => 0, 'liked' => false]);
});

it('does not let a guest add to a count by pressing again and again', function (): void {
    $ids = galleryIds($this->get('/gallery'));

    foreach (range(1, 5) as $press) {
        $this->from('/gallery')->post("/gallery/{$ids[3]}/like");
    }

    // Five presses: liked, taken back, liked, taken back, liked.
    expect(Spotting::find($ids[3])?->likes)->toBe(1);
});

it('does not fill the heart for another guest', function (): void {
    $ids = galleryIds($this->get('/gallery'));
    $this->from('/gallery')->post("/gallery/{$ids[3]}/like");

    // Leave only the fifty shown, so the liked photo is certain to be dealt
    // again to the new visitor and this cannot pass vacuously.
    Spotting::query()->whereKeyNot($ids)->delete();
    $this->flushSession();

    $spottings = collect(gallerySpottings($this->get('/gallery')));

    expect($spottings->firstWhere('id', $ids[3]))->toMatchArray(['likes' => 1, 'liked' => false])
        ->and($spottings->where('liked', true))->toBeEmpty();
});

it('never takes a guest\'s second press below zero', function (): void {
    $ids = galleryIds($this->get('/gallery'));
    $this->from('/gallery')->post("/gallery/{$ids[3]}/like");

    // The count was reset since, by staff tidying up, say.
    Spotting::query()->whereKey($ids[3])->update(['likes' => 0]);

    $this->from('/gallery')->post("/gallery/{$ids[3]}/like");

    expect(Spotting::find($ids[3])?->likes)->toBe(0);
});

it('does not fill the heart for someone else\'s like', function (): void {
    $ids = galleryIds($this->actingAs(User::factory()->create())->get('/gallery'));
    $this->from('/gallery')->post("/gallery/{$ids[3]}/like");

    $this->actingAs(User::factory()->create());
    $spottings = gallerySpottings($this->get('/gallery'));

    // The liked photo is on the page, with its like, so this is not vacuous.
    expect($spottings[3]['id'])->toBe($ids[3])
        ->and($spottings[3]['likes'])->toBe(1)
        ->and(array_filter($spottings, fn (array $spotting): bool => $spotting['liked']))->toBe([]);
});
