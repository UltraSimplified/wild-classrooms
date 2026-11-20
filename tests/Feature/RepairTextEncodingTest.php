<?php

declare(strict_types=1);

use App\Models\Challenge;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

/** A curly apostrophe as Windows-1252 stores it: one byte, 0x92. */
function legacyApostropheText(): string
{
    return "Old Man\x92s Beard";
}

it('converts windows-1252 text to utf-8', function (): void {
    $challenge = Challenge::factory()->create();

    // Write the bad bytes past the model so nothing sanitises them on the way in.
    DB::table('challenges')->where('id', $challenge->id)
        ->update(['title' => legacyApostropheText()]);

    expect(mb_check_encoding(DB::table('challenges')->where('id', $challenge->id)->value('title'), 'UTF-8'))
        ->toBeFalse();

    $this->artisan('app:repair-text-encoding')->assertSuccessful();

    $repaired = DB::table('challenges')->where('id', $challenge->id)->value('title');

    expect($repaired)->toBe('Old Man’s Beard')
        ->and(mb_check_encoding($repaired, 'UTF-8'))->toBeTrue();
});

it('leaves text that is already valid utf-8 alone', function (): void {
    $challenge = Challenge::factory()->create(['title' => 'Old Man’s Beard']);
    $group = Group::factory()->create(['title' => 'Rodborough Beavers']);

    $this->artisan('app:repair-text-encoding')->assertSuccessful();

    expect($challenge->fresh()->title)->toBe('Old Man’s Beard')
        ->and($group->fresh()->title)->toBe('Rodborough Beavers');
});

it('changes nothing on a dry run', function (): void {
    $challenge = Challenge::factory()->create();
    DB::table('challenges')->where('id', $challenge->id)
        ->update(['title' => legacyApostropheText()]);

    $this->artisan('app:repair-text-encoding', ['--dry-run' => true])->assertSuccessful();

    expect(DB::table('challenges')->where('id', $challenge->id)->value('title'))
        ->toBe(legacyApostropheText());
});

it('can run twice without changing anything the second time', function (): void {
    $challenge = Challenge::factory()->create();
    DB::table('challenges')->where('id', $challenge->id)
        ->update(['title' => legacyApostropheText()]);

    $this->artisan('app:repair-text-encoding')->assertSuccessful();
    $once = DB::table('challenges')->where('id', $challenge->id)->value('title');

    $this->artisan('app:repair-text-encoding')->assertSuccessful();

    expect(DB::table('challenges')->where('id', $challenge->id)->value('title'))->toBe($once);
});

it('renders a page whose text came from windows-1252 without blanking it', function (): void {
    // This is what actually broke: json_encode refuses invalid UTF-8, so
    // Inertia shipped no page object and the whole app failed to mount.
    $challenge = Challenge::factory()->create(['month' => (int) now()->format('n')]);
    DB::table('challenges')->where('id', $challenge->id)
        ->update(['title' => legacyApostropheText()]);

    $this->artisan('app:repair-text-encoding')->assertSuccessful();

    // Inertia escapes the apostrophe inside data-page, so assert on the prop
    // rather than the raw HTML: what matters is that a page object exists at
    // all, which is exactly what json_encode was failing to produce.
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where(
                'challenges.0.title',
                fn (string $title): bool => str_contains($title, 'Old Man')
                    && mb_check_encoding($title, 'UTF-8'),
            )
        );
});
