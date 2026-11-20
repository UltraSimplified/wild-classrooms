<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Challenge;
use App\Models\Group;
use App\Models\Post;
use App\Models\Role;
use App\Models\Spotting;
use App\Models\Unit;
use App\Models\User;

it('links a user to the group they own', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $user->id]);

    expect($user->group)->not->toBeNull()
        ->and($user->group->id)->toBe($group->id)
        ->and($group->owner->id)->toBe($user->id);
});

it('reaches a group\'s spottings through its units', function (): void {
    $group = Group::factory()->create();
    $unit = Unit::factory()->create(['group_id' => $group->id]);
    Spotting::factory()->count(3)->create(['unit_id' => $unit->id]);

    // There is no group_id on spottings, so this must traverse units.
    expect($group->spottings)->toHaveCount(3);
});

it('does not pick up spottings belonging to another group', function (): void {
    $mine = Unit::factory()->create();
    $theirs = Unit::factory()->create();
    Spotting::factory()->count(2)->create(['unit_id' => $mine->id]);
    Spotting::factory()->count(5)->create(['unit_id' => $theirs->id]);

    expect($mine->group->spottings)->toHaveCount(2);
});

it('links a challenge to its category and spottings', function (): void {
    $category = Category::factory()->create();
    $challenge = Challenge::factory()->create(['category_id' => $category->id]);
    Spotting::factory()->count(2)->create(['challenge_id' => $challenge->id]);

    expect($challenge->category->id)->toBe($category->id)
        ->and($challenge->spottings)->toHaveCount(2)
        ->and($category->challenges)->toHaveCount(1);
});

it('links a post to its author via author_id', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->create(['author_id' => $author->id]);

    expect($post->author->id)->toBe($author->id)
        ->and($author->posts)->toHaveCount(1);
});

it('reports role membership', function (): void {
    $user = User::factory()->create();
    $admin = Role::factory()->create(['name' => 'admin']);
    $user->roles()->attach($admin);

    expect($user->hasRole('admin'))->toBeTrue()
        ->and($user->hasRole('member'))->toBeFalse()
        ->and($user->hasAnyRole(['member', 'admin']))->toBeTrue()
        ->and($user->hasAnyRole(['member']))->toBeFalse();
});
