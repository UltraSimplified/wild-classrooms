<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Challenge;
use App\Models\Spotting;
use App\Models\Unit;
use App\Services\Trees\Season;
use App\Services\Trees\TreeRenderer;
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

it('heaps snow against the foot of the tree in winter', function (): void {
    $this->travelTo(Carbon::create(2026, 1, 15));

    // The heap rises highest where it has banked against the trunk, over a
    // cool shadow drawn from the same outline.
    expect($this->get('/tree.svg/'.memberWithALeaf()->id)->getContent())
        ->toContain('C786,1098 806,1078 830,1076')
        ->toContain('fill="#dfe7ee" transform="translate(0,8)"');
});

it('piles fallen leaves around the base in autumn', function (): void {
    $this->travelTo(Carbon::create(2026, 10, 15));

    $svg = $this->get('/tree.svg/'.memberWithALeaf()->id)->getContent();

    // Twenty fallen leaves, each with a midrib, on top of the one on the tree.
    expect(substr_count($svg, '<path d="M-13,0 L13,0"'))->toBe(20);
});

it('grows grass round the base in spring', function (): void {
    $this->travelTo(Carbon::create(2026, 4, 15));

    $svg = $this->get('/tree.svg/'.memberWithALeaf()->id)->getContent();

    expect(substr_count($svg, 'M-3.5,0 C-3,-14'))->toBe(46)
        // Flowers are a summer thing.
        ->and($svg)->not->toContain('#e8b923')
        ->and($svg)->not->toContain('#c99a12');
});

it('puts daisies and buttercups in longer grass in summer', function (): void {
    $this->travelTo(Carbon::create(2026, 7, 15));

    $svg = $this->get('/tree.svg/'.memberWithALeaf()->id)->getContent();

    // Each flower colour is used by that flower and nothing else.
    expect(substr_count($svg, 'M-3.5,0 C-3,-14'))->toBe(58)
        ->and(substr_count($svg, '#e8b923'))->toBe(6)   // daisy centres
        ->and(substr_count($svg, '#c99a12'))->toBe(5);  // buttercup centres
});

it('draws the same tree on every request, whatever the season', function (string $date): void {
    // Grass, flowers and fallen leaves are placed from fixed positions or a
    // hash of their index, never at random. A random scatter would give every
    // request a new ETag, and no browser could reuse a tree it already had.
    $this->travelTo(Carbon::parse($date));
    $unit = memberWithALeaf();

    $first = $this->get("/tree.svg/{$unit->id}")->headers->get('etag');
    $second = $this->get("/tree.svg/{$unit->id}")->headers->get('etag');

    expect($second)->toBe($first);
})->with([
    'spring' => '2026-04-15',
    'summer' => '2026-07-15',
    'autumn' => '2026-10-15',
    'winter' => '2027-01-15',
]);

it('blossoms in summer and only in summer', function (): void {
    $renderer = app(TreeRenderer::class);

    // Roughly two leaves in three carry a flower; which ones is fixed per leaf.
    expect(substr_count($renderer->renderComplete(Season::Summer), 'wc-blossom'))->toBe(42)
        ->and($renderer->renderComplete(Season::Spring))->not->toContain('wc-blossom')
        ->and($renderer->renderComplete(Season::Autumn))->not->toContain('wc-blossom')
        ->and($renderer->renderComplete(Season::Winter))->not->toContain('wc-blossom');
});

it('leaves a bare tree without blossom, even in summer', function (): void {
    // The flowers come with the leaves a member has earned, not for nothing.
    expect(app(TreeRenderer::class)->render(collect(), Season::Summer))
        ->not->toContain('wc-blossom');
});

it('never positions a blossom on the group that summer grows', function (): void {
    // Summer sets the CSS transform property on each group in the leaf layer
    // to grow it, and a CSS transform replaces a transform attribute. A flower
    // positioned on that group was drawn at the canvas origin, off the edge —
    // present in the SVG, invisible on the page. Its position lives one level in.
    $svg = app(TreeRenderer::class)->renderComplete(Season::Summer);

    expect($svg)->not->toMatch('/<g class="wc-blossom"[^>]*transform=/')
        ->and(substr_count($svg, '<g class="wc-blossom"><g transform="translate('))->toBe(42);
});

it('keeps existing blossom in place when a new leaf grows', function (): void {
    // Each flower is decided by its own leaf's id, not its place in the list,
    // so a child earning a leaf adds blossom rather than reshuffling it.
    $this->travelTo(Carbon::create(2026, 7, 15));
    $unit = memberWithALeaf();

    $positions = function () use ($unit): array {
        preg_match_all('/wc-blossom"><g transform="([^"]+)"/', $this->get("/tree.svg/{$unit->id}")->getContent(), $m);

        return $m[1];
    };

    $before = $positions();

    // Otherwise the comparison below would pass for nothing.
    expect($before)->not->toBeEmpty();

    $birds = Category::factory()->create(['title' => 'Birds']);
    foreach (range(2, 6) as $month) {
        $challenge = Challenge::factory()->create(['category_id' => $birds->id, 'month' => $month]);
        Spotting::factory()->create(['unit_id' => $unit->id, 'challenge_id' => $challenge->id]);
    }

    $after = $positions();

    expect(count($after))->toBeGreaterThan(count($before))
        ->and(array_diff($before, $after))->toBe([]);
});
