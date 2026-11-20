<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Challenge;
use App\Models\Spotting;
use App\Models\Unit;
use App\Services\Trees\TreeRenderer;

beforeEach(function (): void {
    $this->renderer = app(TreeRenderer::class);
});

it('grows no leaves for a member who has spotted nothing', function (): void {
    $svg = $this->renderer->render(collect());

    expect($svg)->toContain('<svg')
        ->toContain('id="Tree"')
        ->and($svg)->not->toContain('id="Jan_L_1"');
});

it('grows a leaf for the month and category spotted', function (): void {
    $plants = Category::factory()->create(['title' => 'Plants']);
    $challenge = Challenge::factory()->create(['category_id' => $plants->id, 'month' => 1]);
    $spotting = Spotting::factory()->create(['challenge_id' => $challenge->id]);

    $spottings = Spotting::with('challenge.category')->whereKey($spotting->id)->get();

    expect($this->renderer->render($spottings))->toContain('id="Jan_L_1"');
});

it('does not grow a january leaf for a wild spotting that has no month', function (): void {
    // The "Wild spotting" challenge has month = NULL in production. The old
    // code fed that null to strtotime, which failed, and date() fell back to
    // the epoch — so every wild spotting silently grew a January leaf.
    $wild = Category::factory()->create(['title' => 'Wild spotting']);
    $challenge = Challenge::factory()->create(['category_id' => $wild->id, 'month' => null]);
    $spotting = Spotting::factory()->create(['challenge_id' => $challenge->id]);

    $spottings = Spotting::with('challenge.category')->whereKey($spotting->id)->get();

    expect($this->renderer->spottedPairs($spottings))->toBe([])
        ->and($this->renderer->render($spottings))->not->toContain('id="Jan_L_1"');
});

it('reports every month and category pair a member has found', function (): void {
    $birds = Category::factory()->create(['title' => 'Birds']);
    $trees = Category::factory()->create(['title' => 'Trees']);

    $unit = Unit::factory()->create();
    $march = Challenge::factory()->create(['category_id' => $birds->id, 'month' => 3]);
    $june = Challenge::factory()->create(['category_id' => $trees->id, 'month' => 6]);

    Spotting::factory()->create(['unit_id' => $unit->id, 'challenge_id' => $march->id]);
    Spotting::factory()->create(['unit_id' => $unit->id, 'challenge_id' => $june->id]);

    $spottings = Spotting::with('challenge.category')->where('unit_id', $unit->id)->get();

    expect($this->renderer->spottedPairs($spottings))->toBe([
        'Mar' => ['Birds' => true],
        'Jun' => ['Trees' => true],
    ]);
});

it('draws one leaf however many times the same challenge is spotted', function (): void {
    $plants = Category::factory()->create(['title' => 'Plants']);
    $challenge = Challenge::factory()->create(['category_id' => $plants->id, 'month' => 1]);
    $unit = Unit::factory()->create();

    Spotting::factory()->count(4)->create([
        'unit_id' => $unit->id,
        'challenge_id' => $challenge->id,
    ]);

    $spottings = Spotting::with('challenge.category')->where('unit_id', $unit->id)->get();

    expect(substr_count($this->renderer->render($spottings), 'id="Jan_L_1"'))->toBe(1);
});

it('uses a viewBox that fits the whole drawing', function (): void {
    // The original artwork declared viewBox="0 0 1200 1200" while the fully
    // leafed tree actually spans x 370..1231, so the rightmost branches were
    // clipped — on the old site too. Measured in a browser with getBBox();
    // this guards the corrected box rather than re-measuring it.
    $svg = $this->renderer->renderComplete();

    expect($svg)->toContain('viewBox="360 10 881 1181"')
        ->and($svg)->not->toContain('viewBox="0 0 1200 1200"');
});

it('puts every leaf on the complete tree', function (): void {
    expect(substr_count($this->renderer->renderComplete(), '_L_'))->toBe(60);
});
