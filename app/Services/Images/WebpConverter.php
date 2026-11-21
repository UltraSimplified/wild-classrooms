<?php

declare(strict_types=1);

namespace App\Services\Images;

use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Re-encodes an uploaded image as WebP.
 *
 * Every upload route goes through here, the front-end forms and Nova alike, so
 * whatever arrives is stored as WebP: turned upright, shrunk to fit if a limit
 * is given, and stripped of its metadata. The stripping matters on this site:
 * a phone photo's EXIF can carry the GPS position it was taken at, and these
 * are children's photos served from a public directory.
 */
final class WebpConverter
{
    public const EXTENSION = 'webp';

    /** Visually indistinguishable from the originals at the sizes shown. */
    private const QUALITY = 80;

    /**
     * Convert an image file, returning the WebP bytes.
     */
    public function convertFile(string $path, ?int $maxEdge = null): string
    {
        return $this->convert(Image::decodePath($path), $maxEdge);
    }

    /**
     * Convert an already-decoded image, for callers that read it first.
     */
    public function convert(ImageInterface $image, ?int $maxEdge = null): string
    {
        $image->orient();

        // scaleDown never enlarges an image that is already smaller.
        if ($maxEdge !== null) {
            $image->scaleDown($maxEdge, $maxEdge);
        }

        return $image->encode(new WebpEncoder(quality: self::QUALITY, strip: true))->toString();
    }
}
