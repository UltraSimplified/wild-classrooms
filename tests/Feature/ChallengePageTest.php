<?php

declare(strict_types=1);

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
