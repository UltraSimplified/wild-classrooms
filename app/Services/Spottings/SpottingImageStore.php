<?php

declare(strict_types=1);

namespace App\Services\Spottings;

use App\Services\Images\WebpConverter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Stores a spotting photo as WebP, resized and rotated upright.
 *
 * Uses Intervention Image v4: v2's Image::make() and ->orientate() are gone,
 * replaced by Image::decodePath() and ->orient(). There is no read() on the v4
 * facade, so calling one would have failed at runtime.
 */
final class SpottingImageStore
{
    private const MAX_EDGE = 1200;

    /** The public directory uploads land in, relative to public/. */
    private const DIRECTORY = 'uploads';

    public function __construct(private readonly WebpConverter $converter) {}

    /**
     * @return array{path: string, exif: array<string, mixed>|null}
     */
    public function store(UploadedFile $file, string $challengeTitle): array
    {
        $image = Image::decodePath($file->getRealPath());

        // Read before converting, which strips it from the stored file.
        $exif = $this->readExif($image->exif());

        $filename = time().'-'.Str::slug($challengeTitle).'.'.WebpConverter::EXTENSION;
        $directory = public_path(self::DIRECTORY);

        File::ensureDirectoryExists($directory);
        File::put($directory.'/'.$filename, $this->converter->convert($image, self::MAX_EDGE));

        return [
            'path' => '/'.self::DIRECTORY.'/'.$filename,
            'exif' => $exif,
        ];
    }

    /**
     * Remove a stored photo, ignoring one that has already gone.
     */
    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        // Stored as "/uploads/name.webp"; never trust it to escape public/.
        $file = public_path(ltrim($path, '/'));

        if (! str_starts_with(realpath(dirname($file)) ?: '', public_path(self::DIRECTORY))) {
            return;
        }

        File::delete($file);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readExif(mixed $exif): ?array
    {
        if (is_array($exif)) {
            return $exif;
        }

        if (is_object($exif) && method_exists($exif, 'toArray')) {
            /** @var array<string, mixed> $array */
            $array = $exif->toArray();

            return $array;
        }

        return null;
    }
}
