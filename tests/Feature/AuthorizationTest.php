<?php

declare(strict_types=1);

use App\Models\Challenge;
use App\Models\Group;
use App\Models\Spotting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/*
 * These cover the two IDOR holes in the old controllers. Both were reachable
 * by any signed-in user against any of the 91 groups.
 */

it('will not let a user edit a group they do not own', function (): void {
    $intruder = User::factory()->create();
    $victimGroup = Group::factory()->create(['title' => 'Someone Else']);

    // The old form posted group_id, and the controller trusted it.
    $this->actingAs($intruder)->post('/profile/update', [
        'group_id' => $victimGroup->id,
        'title' => 'Hijacked',
        'type' => 'Group',
        'address_line_1' => '1 Nowhere',
        'city' => 'Stroud',
        'postal_code' => 'GL5 1AF',
    ])->assertForbidden();

    expect($victimGroup->fresh()->title)->toBe('Someone Else');
});

it('edits only the signed-in user\'s own group, whatever group_id is posted', function (): void {
    $owner = User::factory()->create();
    $mine = Group::factory()->create(['owner_id' => $owner->id, 'title' => 'Mine']);
    $theirs = Group::factory()->create(['title' => 'Theirs']);

    $this->actingAs($owner)->post('/profile/update', [
        'group_id' => $theirs->id, // ignored
        'title' => 'Renamed',
        'type' => 'Family',
        'address_line_1' => '1 Nowhere',
        'city' => 'Stroud',
        'postal_code' => 'GL5 1AF',
    ])->assertRedirect();

    expect($mine->fresh()->title)->toBe('Renamed')
        ->and($theirs->fresh()->title)->toBe('Theirs');
});

it('will not let a user file a spotting against another group\'s member', function (): void {
    $intruder = User::factory()->create();
    Group::factory()->create(['owner_id' => $intruder->id]);

    $otherUnit = Unit::factory()->create();

    $this->actingAs($intruder)->post('/spottings/add', [
        'unit_id' => $otherUnit->id,
        'challenge_id' => Challenge::factory()->create()->id,
        'notes' => 'Not mine to add',
    ])->assertForbidden();

    expect(Spotting::where('unit_id', $otherUnit->id)->count())->toBe(0);
});

it('lets a user file a spotting against their own member', function (): void {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $unit = Unit::factory()->create(['group_id' => $group->id]);
    $challenge = Challenge::factory()->create();

    $this->actingAs($owner)->post('/spottings/add', [
        'unit_id' => $unit->id,
        'challenge_id' => $challenge->id,
        'notes' => 'A robin on the fence',
        'gallery' => true,
    ])->assertRedirect();

    expect(Spotting::where('unit_id', $unit->id)->count())->toBe(1);
});

it('will not let a user edit or delete another group\'s spotting', function (): void {
    $intruder = User::factory()->create();
    Group::factory()->create(['owner_id' => $intruder->id]);

    $spotting = Spotting::factory()->create();

    $this->actingAs($intruder)->get("/spottings/edit/{$spotting->id}")->assertForbidden();
    $this->actingAs($intruder)->delete("/spottings/{$spotting->id}")->assertForbidden();

    expect(Spotting::find($spotting->id))->not->toBeNull();
});

it('rejects an oversized or non-image upload', function (): void {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $unit = Unit::factory()->create(['group_id' => $group->id]);
    $challenge = Challenge::factory()->create();

    // The old upload had no validation at all.
    $this->actingAs($owner)->post('/spottings/add', [
        'unit_id' => $unit->id,
        'challenge_id' => $challenge->id,
        'picture' => UploadedFile::fake()->create('payload.php', 100, 'application/x-php'),
    ])->assertSessionHasErrors('picture');

    expect(Spotting::count())->toBe(0);
});

it('requires a sign-in for the pages that change things', function (): void {
    $spotting = Spotting::factory()->create();

    $this->get('/spottings/add')->assertRedirect('/login');
    $this->get('/profile/edit')->assertRedirect('/login');
    $this->get("/spottings/edit/{$spotting->id}")->assertRedirect('/login');
});
