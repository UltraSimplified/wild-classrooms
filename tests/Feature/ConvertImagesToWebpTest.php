<?php

declare(strict_types=1);

use App\Models\Challenge;
use App\Models\Group;
use App\Models\Spotting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
 * The command converts images already stored, so each test builds its own
 * public/ in a throwaway directory, and a fake private disk for the originals
 * the command moves aside.
 */

beforeEach(function (): void {
    $this->base = sys_get_temp_dir().'/wc-convert-'.Str::random(8);
    File::ensureDirectoryExists($this->base.'/public/uploads');
    File::ensureDirectoryExists($this->base.'/public/storage');
    $this->app->usePublicPath($this->base.'/public');

    Storage::fake('local');
});

afterEach(function (): void {
    File::deleteDirectory($this->base);
});

/**
 * Write a real image into public/ at the given path.
 */
function photo(string $relative, int $width = 400, int $height = 300): void
{
    $extension = Str::afterLast($relative, '.');
    $fake = UploadedFile::fake()->image("photo.{$extension}", $width, $height);

    File::put(public_path($relative), (string) file_get_contents($fake->getPathname()));
}

function webpAt(string $relative): bool
{
    $bytes = (string) file_get_contents(public_path($relative));

    return str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP';
}

it('converts a spotting photo and moves the original out of public', function (): void {
    photo('uploads/robin.jpg', 3000, 2000);
    $spotting = Spotting::factory()->create(['picture' => '/uploads/robin.jpg']);

    $this->artisan('app:convert-images-to-webp')
        ->expectsOutputToContain('Converted 1 file')
        ->assertSuccessful();

    expect($spotting->refresh()->picture)->toBe('/uploads/robin.webp')
        ->and(webpAt('uploads/robin.webp'))->toBeTrue()
        ->and(array_slice((array) getimagesize(public_path('uploads/robin.webp')), 0, 2))->toBe([1200, 800])
        // Not deleted, but no longer served.
        ->and(file_exists(public_path('uploads/robin.jpg')))->toBeFalse();

    Storage::disk('local')->assertExists('webp-originals/uploads/robin.jpg');

    expect(Storage::disk('local')->get('webp-originals/manifest.csv'))
        ->toContain("spottings,picture,{$spotting->id},/uploads/robin.jpg,/uploads/robin.webp");
});

it('converts images stored under public/storage, keeping each value\'s form', function (): void {
    photo('storage/xkrVTisUqLWjkl.jpeg');
    photo('storage/1589805630-harris.png');

    $challenge = Challenge::factory()->create(['image' => 'xkrVTisUqLWjkl.jpeg']);
    $group = Group::factory()->create(['image' => '/1589805630-harris.png']);

    $this->artisan('app:convert-images-to-webp')->assertSuccessful();

    expect($challenge->refresh()->image)->toBe('xkrVTisUqLWjkl.webp')
        ->and($group->refresh()->image)->toBe('/1589805630-harris.webp')
        ->and(webpAt('storage/xkrVTisUqLWjkl.webp'))->toBeTrue()
        ->and(webpAt('storage/1589805630-harris.webp'))->toBeTrue();

    Storage::disk('local')->assertExists('webp-originals/storage/xkrVTisUqLWjkl.jpeg');
    Storage::disk('local')->assertExists('webp-originals/storage/1589805630-harris.png');
});

it('leaves a record alone when its file is missing or unreadable', function (): void {
    File::put(public_path('uploads/broken.jpg'), 'not an image');

    $missing = Spotting::factory()->create(['picture' => '/uploads/gone.jpg']);
    $broken = Spotting::factory()->create(['picture' => '/uploads/broken.jpg']);

    $this->artisan('app:convert-images-to-webp', ['-v' => true])
        ->expectsOutputToContain("spottings #{$missing->id}: /uploads/gone.jpg was not found")
        ->expectsOutputToContain("spottings #{$broken->id}: /uploads/broken.jpg could not be read")
        ->assertSuccessful();

    expect($missing->refresh()->picture)->toBe('/uploads/gone.jpg')
        ->and($broken->refresh()->picture)->toBe('/uploads/broken.jpg')
        ->and(file_exists(public_path('uploads/broken.jpg')))->toBeTrue();
});

it('never touches a file outside the image directories', function (): void {
    File::put($this->base.'/secret.jpg', 'private');
    $spotting = Spotting::factory()->create(['picture' => '/uploads/../../secret.jpg']);

    $this->artisan('app:convert-images-to-webp')->assertSuccessful();

    expect($spotting->refresh()->picture)->toBe('/uploads/../../secret.jpg')
        ->and(file_exists($this->base.'/secret.jpg'))->toBeTrue();
});

it('changes nothing on a dry run', function (): void {
    photo('uploads/robin.jpg');
    $spotting = Spotting::factory()->create(['picture' => '/uploads/robin.jpg']);

    $this->artisan('app:convert-images-to-webp', ['--dry-run' => true])
        ->expectsOutputToContain('Would convert 1 file')
        ->expectsOutputToContain('Dry run: nothing was changed')
        ->assertSuccessful();

    expect($spotting->refresh()->picture)->toBe('/uploads/robin.jpg')
        ->and(file_exists(public_path('uploads/robin.jpg')))->toBeTrue()
        ->and(file_exists(public_path('uploads/robin.webp')))->toBeFalse()
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('converts a file several records share once, and points them all at it', function (): void {
    photo('storage/shared.jpeg');

    $first = Challenge::factory()->create(['image' => 'shared.jpeg']);
    $second = Challenge::factory()->create(['image' => 'shared.jpeg']);

    $this->artisan('app:convert-images-to-webp')
        ->expectsOutputToContain('Converted 1 file')
        ->assertSuccessful();

    expect($first->refresh()->image)->toBe('shared.webp')
        ->and($second->refresh()->image)->toBe('shared.webp')
        ->and(webpAt('storage/shared.webp'))->toBeTrue();
});

it('numbers a name that two originals would otherwise share', function (): void {
    photo('uploads/snail.jpg');
    photo('uploads/snail.png');

    $jpeg = Spotting::factory()->create(['picture' => '/uploads/snail.jpg']);
    $png = Spotting::factory()->create(['picture' => '/uploads/snail.png']);

    $this->artisan('app:convert-images-to-webp')->assertSuccessful();

    expect($jpeg->refresh()->picture)->toBe('/uploads/snail.webp')
        ->and($png->refresh()->picture)->toBe('/uploads/snail-2.webp')
        ->and(webpAt('uploads/snail.webp'))->toBeTrue()
        ->and(webpAt('uploads/snail-2.webp'))->toBeTrue();
});

it('finds nothing left to do when run a second time', function (): void {
    photo('uploads/robin.jpg');
    $spotting = Spotting::factory()->create(['picture' => '/uploads/robin.jpg']);

    $this->artisan('app:convert-images-to-webp')->assertSuccessful();
    $this->artisan('app:convert-images-to-webp')
        ->expectsOutputToContain('Nothing to convert.')
        ->assertSuccessful();

    expect($spotting->refresh()->picture)->toBe('/uploads/robin.webp');
});

it('works when public/storage is the storage symlink, as on a deployed server', function (): void {
    File::deleteDirectory(public_path('storage'));
    File::ensureDirectoryExists($this->base.'/app-public');
    symlink($this->base.'/app-public', public_path('storage'));

    photo('storage/xkrVTisUqLWjkl.jpeg');
    $challenge = Challenge::factory()->create(['image' => 'xkrVTisUqLWjkl.jpeg']);

    $this->artisan('app:convert-images-to-webp')->assertSuccessful();

    expect($challenge->refresh()->image)->toBe('xkrVTisUqLWjkl.webp')
        ->and(webpAt('storage/xkrVTisUqLWjkl.webp'))->toBeTrue()
        ->and(file_exists($this->base.'/app-public/xkrVTisUqLWjkl.jpeg'))->toBeFalse();

    Storage::disk('local')->assertExists('webp-originals/storage/xkrVTisUqLWjkl.jpeg');
});
