<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Challenge;
use App\Models\Spotting;
use App\Models\Unit;
use Illuminate\Support\Carbon;

it('serves the tree as an svg image', function (): void {
    $unit = Unit::factory()->create();

    $this->get("/tree.svg/{$unit->id}")
        ->assertOk()
        ->assertHeader('content-type', 'image/svg+xml');
});

it('shows a new leaf as soon as a spotting is added', function (): void {
    // The first version of this endpoint sent Cache-Control: max-age=300, so
    // a child who had just added a spotting kept seeing their old tree for up
    // to five minutes. The ETag has to change the moment the tree does.
    $plants = Category::factory()->create(['title' => 'Plants']);
    $challenge = Challenge::factory()->create(['category_id' => $plants->id, 'month' => 1]);
    $unit = Unit::factory()->create();

    $before = $this->get("/tree.svg/{$unit->id}");
    $beforeEtag = $before->headers->get('etag');

    expect($before->getContent())->not->toContain('Jan_L_1');

    Spotting::factory()->create([
        'unit_id' => $unit->id,
        'challenge_id' => $challenge->id,
    ]);

    $after = $this->get("/tree.svg/{$unit->id}");

    expect($after->getContent())->toContain('Jan_L_1')
        ->and($after->headers->get('etag'))->not->toBe($beforeEtag);
});

it('lets a browser reuse an unchanged tree', function (): void {
    $unit = Unit::factory()->create();

    $etag = $this->get("/tree.svg/{$unit->id}")->headers->get('etag');

    $this->withHeaders(['If-None-Match' => $etag])
        ->get("/tree.svg/{$unit->id}")
        ->assertStatus(304);
});

it('renames the signpost when the member is renamed', function (): void {
    $unit = Unit::factory()->create(['title' => 'Ash']);

    $first = $this->get("/sign.svg/{$unit->id}");
    expect($first->getContent())->toContain('Ash');

    $unit->update(['title' => 'Rowan']);

    $second = $this->get("/sign.svg/{$unit->id}");

    expect($second->getContent())->toContain('Rowan')
        ->and($second->headers->get('etag'))->not->toBe($first->headers->get('etag'));
});

/*
 * The seasons. Leaves are achievements rather than foliage — once earned they
 * stay — so the tree never goes bare; what changes is how they are drawn.
 */

/** A member with one leaf on the tree, so there is something to colour. */
function memberWithALeaf(): Unit
{
    $plants = Category::factory()->create(['title' => 'Plants']);
    $challenge = Challenge::factory()->create(['category_id' => $plants->id, 'month' => 1]);
    $unit = Unit::factory()->create();

    Spotting::factory()->create(['unit_id' => $unit->id, 'challenge_id' => $challenge->id]);

    return $unit;
}

it('draws the leaves in the artwork colours in spring', function (): void {
    $this->travelTo(Carbon::create(2026, 4, 15));

    $svg = $this->get('/tree.svg/'.memberWithALeaf()->id)->getContent();

    expect($svg)->toContain('#6e9d03')
        ->and($svg)->not->toContain('<style>');
});

it('grows the leaves in summer without recolouring them', function (): void {
    $this->travelTo(Carbon::create(2026, 7, 15));

    $svg = $this->get('/tree.svg/'.memberWithALeaf()->id)->getContent();

    expect($svg)->toContain('scale(1.08)')
        ->and($svg)->toContain('#6e9d03');
});

it('turns the leaves yellow and orange in autumn', function (): void {
    $this->travelTo(Carbon::create(2026, 10, 15));

    $svg = $this->get('/tree.svg/'.memberWithALeaf()->id)->getContent();

    expect($svg)->toContain('#c4651c')
        ->and($svg)->toContain('#d9a62e')
        ->and($svg)->not->toContain('#6e9d03');
});

it('settles snow on the leaves in winter', function (): void {
    $this->travelTo(Carbon::create(2026, 1, 15));

    expect($this->get('/tree.svg/'.memberWithALeaf()->id)->getContent())
        ->toContain('drop-shadow');
});

it('never repaints the trunk, whatever the season', function (): void {
    // The recolour swaps hex values, so it has to stay inside the leaf layer
    // and leave the trunk, drawn in its own browns, untouched.
    $this->travelTo(Carbon::create(2026, 10, 15));

    expect($this->get('/tree.svg/'.memberWithALeaf()->id)->getContent())
        ->toContain('#8f785c');
});

it('sends a fresh drawing once the season turns', function (): void {
    // Without this the ETag would hold a child on an autumn tree well into
    // the winter, which is the same bug the max-age header caused.
    $unit = memberWithALeaf();

    $this->travelTo(Carbon::create(2026, 10, 15));
    $autumn = $this->get("/tree.svg/{$unit->id}")->headers->get('etag');

    $this->travelTo(Carbon::create(2027, 1, 15));
    $winter = $this->get("/tree.svg/{$unit->id}")->headers->get('etag');

    expect($winter)->not->toBe($autumn);
});

it('snows on the branches as well as the leaves in winter', function (): void {
    $this->travelTo(Carbon::create(2026, 1, 15));

    expect($this->get('/tree.svg/'.memberWithALeaf()->id)->getContent())
        ->toContain('.wc-trunk{filter:drop-shadow');
});

it('banks a drift against the foot of the tree in winter', function (): void {
    $this->travelTo(Carbon::create(2026, 1, 15));

    // The drift's two layers: a cool shadow and the snow on top of it.
    expect($this->get('/tree.svg/'.memberWithALeaf()->id)->getContent())
        ->toContain('fill="#dfe7ee"')
        ->toContain('C1030,1180 656,1180 604,1158 Z" fill="#fff"');
});

it('piles fallen leaves around the base in autumn', function (): void {
    $this->travelTo(Carbon::create(2026, 10, 15));

    $svg = $this->get('/tree.svg/'.memberWithALeaf()->id)->getContent();

    // Twenty fallen leaves, each with a midrib, on top of the one on the tree.
    expect(substr_count($svg, '<path d="M-13,0 L13,0"'))->toBe(20);
});

it('draws nothing on the ground in spring or summer', function (string $date): void {
    $this->travelTo(Carbon::parse($date));

    $svg = $this->get('/tree.svg/'.memberWithALeaf()->id)->getContent();

    expect($svg)->not->toContain('M-13,0 L13,0')
        ->and($svg)->not->toContain('#dfe7ee');
})->with(['spring' => '2026-04-15', 'summer' => '2026-07-15']);

it('draws the same autumn tree on every request', function (): void {
    // The fallen leaves are placed from a fixed list, not at random. A random
    // scatter would give every request a new ETag, and no browser would ever
    // be able to reuse a tree it already had.
    $this->travelTo(Carbon::create(2026, 10, 15));
    $unit = memberWithALeaf();

    $first = $this->get("/tree.svg/{$unit->id}")->headers->get('etag');
    $second = $this->get("/tree.svg/{$unit->id}")->headers->get('etag');

    expect($second)->toBe($first);
});

it('draws the name board without its legs', function (): void {
    // The board is cropped at its lower edge, y=26.35 in the artwork, which
    // keeps the descender on a name like "Amy" and drops the two posts.
    $unit = Unit::factory()->create(['title' => 'Amy']);

    expect($this->get("/sign.svg/{$unit->id}")->getContent())
        ->toContain('viewBox="0 0 99 26.7"')
        ->not->toContain('viewBox="0 0 99 60"');
});
