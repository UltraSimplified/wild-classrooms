<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\Spotting;
use App\Models\Unit;
use App\Services\Groups\MemberSync;

beforeEach(function (): void {
    $this->sync = app(MemberSync::class);
    $this->group = Group::factory()->create();
});

it('creates members from names', function (): void {
    $this->sync->sync($this->group, [
        ['id' => null, 'title' => 'Ash'],
        ['id' => null, 'title' => 'Beech'],
    ]);

    expect($this->group->units()->pluck('title')->sort()->values()->all())
        ->toBe(['Ash', 'Beech']);
});

it('renames a member without moving their spottings', function (): void {
    $unit = Unit::factory()->create(['group_id' => $this->group->id, 'title' => 'Ash']);
    $spotting = Spotting::factory()->create(['unit_id' => $unit->id]);

    $this->sync->sync($this->group, [['id' => $unit->id, 'title' => 'Ashley']]);

    expect($unit->fresh()->title)->toBe('Ashley')
        ->and($spotting->fresh()->unit_id)->toBe($unit->id);
});

it('keeps each member\'s spottings when the form order changes', function (): void {
    // The old controller walked the submitted names positionally against the
    // existing member list, so reordering the inputs renamed people into each
    // other's rows and their spottings went with the row.
    $ash = Unit::factory()->create(['group_id' => $this->group->id, 'title' => 'Ash']);
    $beech = Unit::factory()->create(['group_id' => $this->group->id, 'title' => 'Beech']);

    $ashSpotting = Spotting::factory()->create(['unit_id' => $ash->id]);
    $beechSpotting = Spotting::factory()->create(['unit_id' => $beech->id]);

    // Submitted in the opposite order.
    $this->sync->sync($this->group, [
        ['id' => $beech->id, 'title' => 'Beech'],
        ['id' => $ash->id, 'title' => 'Ash'],
    ]);

    expect($ash->fresh()->title)->toBe('Ash')
        ->and($beech->fresh()->title)->toBe('Beech')
        ->and($ashSpotting->fresh()->unit_id)->toBe($ash->id)
        ->and($beechSpotting->fresh()->unit_id)->toBe($beech->id);
});

it('removes a member the form no longer lists, and their spottings', function (): void {
    $keep = Unit::factory()->create(['group_id' => $this->group->id, 'title' => 'Ash']);
    $drop = Unit::factory()->create(['group_id' => $this->group->id, 'title' => 'Beech']);
    $orphan = Spotting::factory()->create(['unit_id' => $drop->id]);

    $this->sync->sync($this->group, [['id' => $keep->id, 'title' => 'Ash']]);

    expect(Unit::find($drop->id))->toBeNull()
        ->and(Spotting::find($orphan->id))->toBeNull()
        ->and(Unit::find($keep->id))->not->toBeNull();
});

it('ignores blank slots', function (): void {
    $this->sync->sync($this->group, [
        ['id' => null, 'title' => 'Ash'],
        ['id' => null, 'title' => ''],
        ['id' => null, 'title' => '   '],
    ]);

    expect($this->group->units()->count())->toBe(1);
});

it('accepts bare name strings as well as id and title pairs', function (): void {
    $this->sync->sync($this->group, ['Ash', 'Beech', null]);

    expect($this->group->units()->count())->toBe(2);
});

it('stops at ten members', function (): void {
    $names = array_map(fn (int $i): array => ['id' => null, 'title' => "Member {$i}"], range(1, 15));

    $this->sync->sync($this->group, $names);

    expect($this->group->units()->count())->toBe(10);
});
