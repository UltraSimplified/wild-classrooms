<?php

declare(strict_types=1);

use App\Models\Spotting;
use App\Support\MediaSize;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

/*
 * Pages send each photo's pixel size with it, so the browser can hold the
 * photo's space open before it loads. Without that, every gallery photo is
 * zero high until it arrives: the columns reshuffle as each one lands, and
 * the browser treats every lazy image as on screen and fetches them all.
 */

beforeEach(function (): void {
    $this->base = sys_get_temp_dir().'/wc-size-'.Str::random(8);
    File::ensureDirectoryExists($this->base.'/public/uploads');
    File::ensureDirectoryExists($this->base.'/public/storage');
    $this->app->usePublicPath($this->base.'/public');
});

afterEach(function (): void {
    File::deleteDirectory($this->base);
});

function storeImage(string $relative, int $width, int $height): void
{
    $fake = UploadedFile::fake()->image('photo.jpg', $width, $height);

    File::put(public_path($relative), (string) file_get_contents($fake->getPathname()));
}

it('reads the size of an image in public/uploads or under the storage link', function (): void {
    storeImage('uploads/robin.jpg', 640, 480);
    storeImage('storage/xkrVTisUqLWjkl.jpeg', 300, 400);

    expect(MediaSize::of('/uploads/robin.jpg'))->toBe(['width' => 640, 'height' => 480])
        ->and(MediaSize::of('xkrVTisUqLWjkl.jpeg'))->toBe(['width' => 300, 'height' => 400]);
});

it('gives no size for anything it cannot measure', function (?string $value): void {
    File::put(public_path('uploads/broken.jpg'), 'not an image');
    File::put($this->base.'/secret.jpg', 'private');

    expect(MediaSize::of($value))->toBeNull();
})->with([
    'nothing stored' => null,
    'empty' => '',
    'a missing file' => '/uploads/gone.jpg',
    'not an image' => '/uploads/broken.jpg',
    'a remote image' => 'https://example.test/robin.jpg',
    'outside public/' => '/uploads/../../secret.jpg',
]);

it('sends each gallery photo with its size', function (): void {
    storeImage('uploads/robin.jpg', 640, 480);
    Spotting::factory()->create(['gallery' => true, 'picture' => '/uploads/robin.jpg']);

    // public/ is a throwaway directory here, with no built asset manifest.
    $this->withoutVite();

    $this->get('/gallery')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('spottings.0.picture', '/uploads/robin.jpg')
        ->where('spottings.0.size', ['width' => 640, 'height' => 480]));
});
