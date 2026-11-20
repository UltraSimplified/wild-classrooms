<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Challenge;
use App\Models\Spotting;
use App\Models\Unit;

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
