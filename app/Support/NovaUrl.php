<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Nova 5 serves its admin pages from a single SPA catch-all route, so there
 * are no named routes for a resource's detail or edit page. These links are
 * built by hand instead, in one place, so the staff emails keep working if
 * NOVA_PATH ever changes.
 */
final class NovaUrl
{
    public static function edit(string $resource, int|string $id): string
    {
        return url(self::path()."/resources/{$resource}/{$id}/edit");
    }

    public static function detail(string $resource, int|string $id): string
    {
        return url(self::path()."/resources/{$resource}/{$id}");
    }

    private static function path(): string
    {
        return '/'.trim((string) config('nova.path', '/nova'), '/');
    }
}
