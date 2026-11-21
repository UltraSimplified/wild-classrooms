<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Challenge;
use App\Models\Group;
use App\Models\Post;
use App\Models\Spotting;
use App\Models\Unit;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the home page with featured spottings and challenges', function (): void {
    $challenge = Challenge::factory()->create(['month' => (int) now()->format('n')]);
    Spotting::factory()->featured()->create(['challenge_id' => $challenge->id]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('challenges', 1)
            ->has('featured', 1)
            ->has('mapGroups')
        );
});

it('lists challenges split into past and upcoming', function (): void {
    $month = (int) now()->format('n');
    Challenge::factory()->create(['month' => $month]);
    Challenge::factory()->create(['month' => $month === 12 ? 1 : $month + 1]);

    $this->get(route('challenges.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Challenges/Index'));
});

it('rejects a month outside 1-12', function (): void {
    $this->get('/month/13')->assertNotFound();
    $this->get('/month/0')->assertNotFound();
    $this->get(route('challenges.month', ['month' => 6]))->assertOk();
});

it('shows a challenge page at the old spottings url', function (): void {
    $challenge = Challenge::factory()->create(['title' => 'Robin']);

    $this->get("/spottings/{$challenge->id}/robin")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Challenges/Show')
            ->where('challenge.title', 'Robin')
        );
});

it('only shows gallery-flagged spottings with pictures in the gallery', function (): void {
    Spotting::factory()->create(['gallery' => true, 'picture' => '/uploads/a.jpg']);
    Spotting::factory()->create(['gallery' => false, 'picture' => '/uploads/b.jpg']);
    Spotting::factory()->create(['gallery' => true, 'picture' => null]);

    $this->get(route('gallery.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Gallery/Index')
            ->has('spottings', 1)
        );
});

it('renders a group woodland with its members', function (): void {
    $group = Group::factory()->create();
    Unit::factory()->count(3)->create(['group_id' => $group->id]);

    $this->get(route('groups.woodland', ['group' => $group->id, 'slug' => 'x']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Groups/Woodland')
            ->has('members', 3)
            ->where('isMyGroup', false)
        );
});

it('sends each member\'s whole name for their name board', function (): void {
    // The board used to show only the first word, so every tree in a group
    // of "Class 1" to "Class 6" read "Class". It is the only name on the card
    // now, so it has to be the whole thing.
    $group = Group::factory()->create();
    Unit::factory()->create(['group_id' => $group->id, 'title' => 'Class 4']);
    Unit::factory()->create(['group_id' => $group->id, 'title' => 'Class 5']);

    $this->get(route('groups.woodland', ['group' => $group->id, 'slug' => 'x']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('members.0.title', 'Class 4')
            ->where('members.1.title', 'Class 5')
        );
});

it('renders the dig deeper index and a post', function (): void {
    $post = Post::factory()->create(['title' => 'Winter Wildlife']);

    $this->get(route('posts.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Posts/Index')->has('posts', 1));

    $this->get("/dig-deeper/view/{$post->id}/winter-wildlife")->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Posts/Show')
            ->where('post.title', 'Winter Wildlife')
        );
});

it('renders the static pages', function (): void {
    $this->get(route('pages.about'))->assertOk();
    $this->get(route('pages.terms'))->assertOk();
    $this->get(route('pages.cookie-policy'))->assertOk();
});

it('wraps december to january rather than asking for month 13', function (): void {
    $unit = Unit::factory()->create();
    Category::factory()->create();

    $this->get(route('units.tree', ['unit' => $unit->id, 'slug' => 'x', 'month' => 12]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Units/Tree')
            ->where('month', 12)
            ->where('nextMonth', 1)
        );
});
