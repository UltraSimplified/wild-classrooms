<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The pixel size of a stored image, so a page can hold its space open.
 *
 * An <img> with no width and height has no height at all until it loads. In a
 * grid that makes rows jump; in the gallery's columns, where every photo is a
 * different shape, each one that arrives pushes the rest into other columns.
 * And while they are all zero high, every lazy image counts as on screen, so
 * the browser fetches the whole page of them at once.
 *
 * Only the file's header is read, about 0.15ms a file, so it is done as the
 * page is built rather than stored.
 */
final class MediaSize
{
    /**
     * @return array{width: int, height: int}|null
     */
    public static function of(?string $value): ?array
    {
        $url = MediaUrl::resolve($value);

        // Only files served from public/; a remote URL is left to size itself.
        if ($url === null || ! str_starts_with($url, '/')) {
            return null;
        }

        $path = realpath(public_path(ltrim($url, '/')));
        $public = realpath(public_path());

        if ($path === false || $public === false || ! str_starts_with($path, $public.DIRECTORY_SEPARATOR)) {
            return null;
        }

        $size = @getimagesize($path);

        if ($size === false || $size[0] < 1 || $size[1] < 1) {
            return null;
        }

        return ['width' => $size[0], 'height' => $size[1]];
    }
}
