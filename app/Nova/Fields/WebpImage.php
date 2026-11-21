<?php

declare(strict_types=1);

namespace App\Nova\Fields;

use App\Services\Images\WebpConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Image;

/**
 * Nova's image field, storing whatever is uploaded as WebP.
 *
 * Nova's own field saves the file exactly as it arrives, so staff uploads were
 * the one route that skipped the conversion the front-end forms do.
 */
final class WebpImage extends Image
{
    /**
     * Generous enough for a full-width hero on a high-density screen, while
     * stopping a camera original of 6000px or more being stored as it is.
     */
    private const MAX_EDGE = 2400;

    public function __construct($name, mixed $attribute = null, ?string $disk = null)
    {
        parent::__construct($name, $attribute, $disk);

        // Nova's accept="image/*" only guides the picker. Without this an SVG
        // or a PDF would reach the converter, which cannot read either.
        $this->rules('image');

        $this->store(function (Request $request, $model, string $attribute, string $requestAttribute): array|true {
            $upload = $this->retrieveFileFromRequest($request, $requestAttribute);

            // Nova checks for a file before calling this; true leaves the field as it was.
            if ($upload === null) {
                return true;
            }

            $path = ltrim(rtrim((string) $this->getStorageDir(), '/').'/'.Str::random(40).'.'.WebpConverter::EXTENSION, '/');

            Storage::disk($this->getStorageDisk())->put(
                $path,
                app(WebpConverter::class)->convertFile($upload->getPathname(), self::MAX_EDGE),
            );

            return [$this->attribute => $path];
        });
    }
}
