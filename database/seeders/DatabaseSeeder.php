<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Challenge;
use App\Models\Group;
use App\Models\Post;
use App\Models\Role;
use App\Models\Spotting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local development data, shaped like production: five categories, twelve
 * months of five challenges each, and a handful of groups with members and
 * spottings so the woodland and the trees have something to draw.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'member']);

        $categories = collect(['Trees', 'Birds', 'Butterflies & moths', 'Minibeasts', 'Plants'])
            ->map(fn (string $title): Category => Category::factory()->create(['title' => $title]));

        // Production has exactly one challenge per category per month.
        $challenges = collect();

        foreach (range(1, 12) as $month) {
            foreach ($categories as $category) {
                $challenges->push(Challenge::factory()->create([
                    'category_id' => $category->getKey(),
                    'month' => $month,
                ]));
            }
        }

        // The "Wild spotting" category has a single challenge with no month.
        $wild = Category::factory()->create(['title' => 'Wild spotting']);
        $wildChallenge = Challenge::factory()->create([
            'category_id' => $wild->getKey(),
            'month' => null,
            'title' => 'Wild spotting',
        ]);

        $owner = User::factory()->create([
            'name' => 'Robin Layfield',
            'email' => 'robin@example.test',
        ]);
        $owner->roles()->attach($admin);

        $group = Group::factory()->create([
            'owner_id' => $owner->getKey(),
            'title' => 'Rodborough Beavers',
            'type' => 'Group',
        ]);

        foreach (['Ash', 'Beech', 'Hazel', 'Rowan'] as $index => $name) {
            $unit = Unit::factory()->create([
                'group_id' => $group->getKey(),
                'title' => $name,
            ]);

            // Each member has found a few things, so their trees differ.
            $challenges->random(3 + $index * 2)->each(
                fn (Challenge $challenge) => Spotting::factory()->create([
                    'unit_id' => $unit->getKey(),
                    'challenge_id' => $challenge->getKey(),
                    'gallery' => true,
                    'featured' => $index === 0,
                ])
            );

            Spotting::factory()->create([
                'unit_id' => $unit->getKey(),
                'challenge_id' => $wildChallenge->getKey(),
                'gallery' => true,
            ]);
        }

        // A few other groups so the home page and group list are populated.
        Group::factory()->count(5)->create()->each(function (Group $other): void {
            Unit::factory()->count(2)->create(['group_id' => $other->getKey()]);
        });

        Post::factory()->count(4)->create(['author_id' => $owner->getKey()]);
    }
}
