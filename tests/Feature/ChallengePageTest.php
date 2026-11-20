<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Challenge;
use App\Models\Spotting;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * The species page shows the same spottings three ways, and each view filters
 * differently. These tests pin the three apart, because the port originally
 * shipped only the first and 517 of the 961 live records became invisible.
 */

it('lists every spotting in the journal, including those with no photo', function (): void {
    $challenge = Challenge::factory()->create(['title' => 'Bee']);

    Spotting::factory()->create([
        'challenge_id' => $challenge->id, 'picture' => '/uploads/a.jpg', 'gallery' => true,
    ]);
    Spotting::factory()->create([
        'challenge_id' => $challenge->id, 'picture' => null, 'gallery' => false,
    ]);
    Spotting::factory()->create([
        'challenge_id' => $challenge->id, 'picture' => null, 'gallery' => true,
    ]);

    $this->get(route('challenges.show', $challenge))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Challenges/Show')
            // The photo grid takes gallery entries that have a picture: one of three.
            ->has('spottings', 1)
            // The journal is the only place the other two are ever seen.
            ->has('journal', 3)
        );
});

it('plots only the spottings a group agreed to place', function (): void {
    $challenge = Challenge::factory()->create(['title' => 'Bee']);

    Spotting::factory()->create([
        'challenge_id' => $challenge->id,
        'map' => true, 'latitude' => '51.74111', 'longitude' => '-2.22667',
    ]);

    // Coordinates recorded, but the group did not agree to place them. 52 live
    // rows look like this, so the flag is honoured on its own, not inferred
    // from the presence of coordinates.
    Spotting::factory()->create([
        'challenge_id' => $challenge->id,
        'map' => false, 'latitude' => '51.74111', 'longitude' => '-2.22667',
    ]);

    Spotting::factory()->create([
        'challenge_id' => $challenge->id,
        'map' => true, 'latitude' => null, 'longitude' => null,
    ]);

    $this->get(route('challenges.show', $challenge))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('mapPoints', 1)
            ->has('journal', 3)
        );
});

it('sends coordinates as numbers rather than the strings the column holds', function (): void {
    // latitude and longitude are text columns, and Leaflet needs numbers.
    $challenge = Challenge::factory()->create(['title' => 'Bee']);

    Spotting::factory()->create([
        'challenge_id' => $challenge->id,
        'map' => true, 'latitude' => '51.74111', 'longitude' => '-2.22667',
    ]);

    $this->get(route('challenges.show', $challenge))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('mapPoints.0.latitude', 51.74111)
            ->where('mapPoints.0.longitude', -2.22667)
        );
});

it('puts the most recent spotting at the top of the journal', function (): void {
    $challenge = Challenge::factory()->create(['title' => 'Bee']);

    $older = Spotting::factory()->create(['challenge_id' => $challenge->id, 'owner' => 'Older']);
    Spotting::factory()->create(['challenge_id' => $challenge->id, 'owner' => 'Newer']);

    // created_at is not fillable, so it is set past the model.
    DB::table('spottings')->where('id', $older->id)
        ->update(['created_at' => now()->subYear()]);

    $this->get(route('challenges.show', $challenge))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('journal.0.owner', 'Newer')
            ->where('journal.1.owner', 'Older')
        );
});

/*
 * Stepping sideways through a month. The order is category_id then id, which
 * is what the month page itself lists, so the two cannot disagree.
 */

/** @return array<string, Challenge> three November species, in listing order */
function novemberRow(): array
{
    $categories = Category::factory()->count(3)->create();

    return [
        'first' => Challenge::factory()->create([
            'month' => 11, 'category_id' => $categories[0]->id, 'title' => 'Field Maple',
        ]),
        'middle' => Challenge::factory()->create([
            'month' => 11, 'category_id' => $categories[1]->id, 'title' => 'Starling',
        ]),
        'last' => Challenge::factory()->create([
            'month' => 11, 'category_id' => $categories[2]->id, 'title' => 'Red Admiral',
        ]),
    ];
}

it('offers the species either side of this one in the month', function (): void {
    $row = novemberRow();

    // A December species must not leak into November's sequence.
    Challenge::factory()->create(['month' => 12, 'title' => 'Holly']);

    $this->get(route('challenges.show', $row['middle']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('adjacent.previous.title', 'Field Maple')
            ->where('adjacent.previous.slug', 'field-maple')
            ->where('adjacent.next.title', 'Red Admiral')
        );
});

it('wraps from the first species back round to the last', function (): void {
    $row = novemberRow();

    $this->get(route('challenges.show', $row['first']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('adjacent.previous.title', 'Red Admiral')
            ->where('adjacent.next.title', 'Starling')
        );
});

it('wraps from the last species round to the first', function (): void {
    $row = novemberRow();

    $this->get(route('challenges.show', $row['last']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('adjacent.previous.title', 'Starling')
            ->where('adjacent.next.title', 'Field Maple')
        );
});

it('gives the only species in a month nothing to step to', function (): void {
    // A loop of one would point at itself on both sides.
    $alone = Challenge::factory()->create(['month' => 7, 'title' => 'Dog Rose']);

    $this->get(route('challenges.show', $alone))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('adjacent.previous', null)
            ->where('adjacent.next', null)
        );
});

it('gives a challenge with no month no neighbours at all', function (): void {
    // "Wild spotting" is the live example: it belongs to no month, so it is
    // not part of the year's sequence and has nothing to step to.
    $loose = Challenge::factory()->create(['month' => null, 'title' => 'Wild spotting']);

    $this->get(route('challenges.show', $loose))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('adjacent.previous', null)
            ->where('adjacent.next', null)
        );
});
