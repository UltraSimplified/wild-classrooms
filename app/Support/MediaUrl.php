<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Turns a stored image value into a usable URL.
 *
 * The live data holds three different formats, because images arrived by three
 * different routes over the years:
 *
 *   "/uploads/1578178929-Wild spotting.png"  spotting photos written straight
 *                                            into public/uploads (524 rows)
 *   "xkrVTisUqLWjkl...jpeg"                  Nova uploads on the `public` disk,
 *                                            served via the storage symlink
 *   "/1589805630-harris.jpg"                 group photos the old profile form
 *                                            wrote into public/storage
 *
 * The old Blade templates guessed differently in different places — the home
 * page asked for "/storage/{$spotting->picture}", which resolved to
 * /storage//uploads/... and did not load. Resolving it in one place means every
 * page agrees.
 */
final class MediaUrl
{
    public static function resolve(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        // Already an absolute URL.
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        // Written directly into public/ — use as given.
        if (str_starts_with($value, '/uploads/')) {
            return $value;
        }

        // Anything else lives under the storage symlink, with or without a
        // leading slash of its own.
        return '/storage/'.ltrim($value, '/');
    }
}
