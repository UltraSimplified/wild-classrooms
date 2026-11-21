<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Challenge;
use App\Models\Group;
use App\Models\Spotting;
use App\Models\Unit;
use App\Models\User;
use App\Services\Images\WebpConverter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
 * Every route an image can arrive by stores it as WebP: the spotting form, the
 * group profile form and Nova's image fields. The front-end forms write into
 * public/, so each test points public/ at a throwaway directory rather than
 * leaving files among the real uploads.
 */

beforeEach(function (): void {
    $this->publicPath = sys_get_temp_dir().'/wc-public-'.Str::random(8);
    File::ensureDirectoryExists($this->publicPath);
    $this->app->usePublicPath($this->publicPath);
});

afterEach(function (): void {
    File::deleteDirectory($this->publicPath);
});

function isWebp(string $bytes): bool
{
    return str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP';
}

/**
 * @return array{0: int, 1: int}
 */
function dimensions(string $bytes): array
{
    $size = getimagesizefromstring($bytes);

    expect($size)->not->toBeFalse();

    return [$size[0], $size[1]];
}

/**
 * A JPEG carrying a GPS position, as a phone photo would: 51° 44' 40" N,
 * 2° 13' 1" W, which is Stroud rather than anywhere a child has been.
 */
function jpegWithGps(): UploadedFile
{
    $rational = fn (int ...$parts): string => implode('', array_map(fn (int $part): string => pack('VV', $part, 1), $parts));

    // Little-endian TIFF: IFD0 points at a GPS IFD, whose rationals follow it.
    $tiff = "II\x2A\x00".pack('V', 8)
        .pack('v', 1).pack('vvVV', 0x8825, 4, 1, 26).pack('V', 0)
        .pack('v', 4)
        .pack('vvV', 0x0001, 2, 2)."N\0\0\0"
        .pack('vvVV', 0x0002, 5, 3, 80)
        .pack('vvV', 0x0003, 2, 2)."W\0\0\0"
        .pack('vvVV', 0x0004, 5, 3, 104)
        .pack('V', 0)
        .$rational(51, 44, 40)
        .$rational(2, 13, 1);

    $app1 = "Exif\0\0".$tiff;
    $plain = UploadedFile::fake()->image('photo.jpg', 1600, 1200);
    $jpeg = (string) file_get_contents($plain->getPathname());

    // Insert the APP1 segment straight after the start-of-image marker.
    $bytes = substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($app1) + 2).$app1.substr($jpeg, 2);

    return UploadedFile::fake()->createWithContent('photo.jpg', $bytes);
}

it('stores a spotting photo as WebP, shrunk to 1200px', function (string $extension): void {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $unit = Unit::factory()->create(['group_id' => $group->id]);
    $challenge = Challenge::factory()->create(['title' => 'Red Admiral']);

    $this->actingAs($owner)->post('/spottings/add', [
        'unit_id' => $unit->id,
        'challenge_id' => $challenge->id,
        'picture' => UploadedFile::fake()->image("photo.{$extension}", 3000, 2000),
    ])->assertRedirect();

    $picture = (string) Spotting::sole()->picture;

    expect($picture)->toMatch('#^/uploads/\d+-red-admiral\.webp$#');

    $bytes = File::get(public_path(ltrim($picture, '/')));

    expect(isWebp($bytes))->toBeTrue()
        ->and(dimensions($bytes))->toBe([1200, 800]);
})->with(['jpg', 'png', 'webp']);

it('keeps a photo\'s location for the map but strips it from the stored file', function (): void {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $unit = Unit::factory()->create(['group_id' => $group->id]);
    $photo = jpegWithGps();

    // Without this the test could pass on a photo that never had a location.
    expect(exif_read_data($photo->getPathname()))->toHaveKey('GPSLatitude');

    $this->actingAs($owner)->post('/spottings/add', [
        'unit_id' => $unit->id,
        'challenge_id' => Challenge::factory()->create()->id,
        'picture' => $photo,
    ])->assertRedirect();

    $spotting = Spotting::sole();
    $bytes = File::get(public_path(ltrim((string) $spotting->picture, '/')));

    expect((float) $spotting->latitude)->toBeGreaterThan(51.74)->toBeLessThan(51.75)
        ->and((float) $spotting->longitude)->toBeGreaterThan(-2.22)->toBeLessThan(-2.21)
        ->and(isWebp($bytes))->toBeTrue()
        ->and($bytes)->not->toContain('EXIF')
        ->and($bytes)->not->toContain('Exif');
});

it('stores a group photo as WebP, shrunk to 800px', function (): void {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($owner)->post('/profile/update', [
        'title' => 'Owls Class',
        'type' => 'School',
        'address_line_1' => '1 Lansdown',
        'city' => 'Stroud',
        'postal_code' => 'GL5 1BB',
        'picture' => UploadedFile::fake()->image('class.jpg', 1600, 1200),
    ])->assertRedirect();

    $image = (string) $group->refresh()->image;

    expect($image)->toMatch('#^/uploads/groups/\d+-owls-class\.webp$#');

    $bytes = File::get(public_path(ltrim($image, '/')));

    expect(isWebp($bytes))->toBeTrue()
        ->and(dimensions($bytes))->toBe([800, 600]);
});

it('stores a Nova upload as WebP, shrunk to 2400px', function (): void {
    Storage::fake('public');

    $this->actingAs(staff())
        ->postJson('/nova-api/categories', [
            'title' => 'Birds',
            'image' => UploadedFile::fake()->image('birds.jpg', 3600, 2400),
        ])
        ->assertCreated();

    $image = (string) Category::sole()->image;

    expect($image)->toEndWith('.webp');

    Storage::disk('public')->assertExists($image);

    $bytes = (string) Storage::disk('public')->get($image);

    expect(isWebp($bytes))->toBeTrue()
        ->and(dimensions($bytes))->toBe([2400, 1600]);
});

it('refuses a Nova upload the converter cannot read', function (): void {
    Storage::fake('public');

    $svg = UploadedFile::fake()->createWithContent(
        'logo.svg',
        '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"/>',
    );

    $this->actingAs(staff())
        ->postJson('/nova-api/categories', ['title' => 'Birds', 'image' => $svg])
        ->assertJsonValidationErrors('image');

    expect(Category::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('never enlarges a small image', function (): void {
    $file = UploadedFile::fake()->image('small.png', 300, 200);

    $bytes = app(WebpConverter::class)->convertFile($file->getPathname(), 1200);

    expect(isWebp($bytes))->toBeTrue()
        ->and(dimensions($bytes))->toBe([300, 200]);
});
