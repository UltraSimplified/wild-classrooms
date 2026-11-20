<?php

declare(strict_types=1);

namespace App\Services\Spottings;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Stores a spotting photo, resized and rotated upright.
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

    /**
     * @return array{path: string, exif: array<string, mixed>|null}
     */
    public function store(UploadedFile $file, string $challengeTitle): array
    {
        $image = Image::decodePath($file->getRealPath());

        $exif = $this->readExif($image->exif());

        $filename = time().'-'.Str::slug($challengeTitle).'.'.$this->extension($file);
        $directory = public_path(self::DIRECTORY);

        File::ensureDirectoryExists($directory);

        // scaleDown never enlarges a photo that is already smaller.
        $image->orient()
            ->scaleDown(self::MAX_EDGE, self::MAX_EDGE)
            ->save($directory.'/'.$filename);

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

        // Stored as "/uploads/name.jpg"; never trust it to escape public/.
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

    private function extension(UploadedFile $file): string
    {
        // Prefer the sniffed extension over the one the client claims.
        return $file->extension() ?: $file->getClientOriginalExtension();
    }
}
