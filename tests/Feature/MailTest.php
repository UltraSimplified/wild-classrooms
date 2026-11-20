<?php

declare(strict_types=1);

use App\Mail\GroupRegistered;
use App\Mail\SpottingRecorded;
use App\Models\Challenge;
use App\Models\Group;
use App\Models\Spotting;
use App\Models\User;

it('renders the group registration mail with the full address', function (): void {
    $user = User::factory()->create(['name' => 'Ada Lovelace']);
    $group = Group::factory()->create([
        'title' => 'Rodborough Beavers',
        'address_line_1' => '8 Threadneedle Street',
        'city' => 'Stroud',
        'postal_code' => 'GL5 1AF',
    ]);

    $rendered = (new GroupRegistered('New group registration', $group, $user))->render();

    // The old view read $group->postcode, which does not exist, so the
    // postcode silently never appeared in these emails.
    expect($rendered)
        ->toContain('GL5 1AF')
        ->toContain('8 Threadneedle Street')
        ->toContain('Rodborough Beavers')
        ->toContain('Ada Lovelace');
});

it('renders the spotting mail without needing an authenticated session', function (): void {
    $uploader = User::factory()->create(['name' => 'Grace Hopper']);
    $group = Group::factory()->create(['title' => 'Stroud Wildlife Group']);
    $challenge = Challenge::factory()->create(['title' => 'Robin']);
    $spotting = Spotting::factory()->create([
        'challenge_id' => $challenge->id,
        'location' => 'Stratford Park',
        'what3words' => '///filled.count.soap',
    ]);

    // Nothing is logged in: these mailables are queueable, and the old views
    // called Auth::user() inside the template, which a queued job cannot do.
    expect(auth()->check())->toBeFalse();

    $rendered = (new SpottingRecorded(
        'New spotting', $challenge, $spotting, $uploader, $group
    ))->render();

    expect($rendered)
        ->toContain('Grace Hopper')
        ->toContain('Stroud Wildlife Group')
        ->toContain('Robin')
        ->toContain('Stratford Park')
        // The leading /// is stripped so the link reads as a w3w address.
        ->toContain('filled.count.soap')
        ->not->toContain('///filled.count.soap');
});
