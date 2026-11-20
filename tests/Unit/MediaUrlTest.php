<?php

declare(strict_types=1);

use App\Support\MediaUrl;

it('returns nothing for an empty value', function (): void {
    expect(MediaUrl::resolve(null))->toBeNull()
        ->and(MediaUrl::resolve(''))->toBeNull()
        ->and(MediaUrl::resolve('   '))->toBeNull();
});

it('leaves a spotting photo path alone', function (): void {
    // 524 rows in production look like this: written straight into public/.
    expect(MediaUrl::resolve('/uploads/1578178929-Wild spotting.png'))
        ->toBe('/uploads/1578178929-Wild spotting.png');
});

it('sends a bare nova filename through the storage symlink', function (): void {
    expect(MediaUrl::resolve('xkrVTisUqLWjkltcDb2LPVV28G6saONsGvhKmagH.jpeg'))
        ->toBe('/storage/xkrVTisUqLWjkltcDb2LPVV28G6saONsGvhKmagH.jpeg');
});

it('does not double the slash on a legacy group image', function (): void {
    // The old profile form stored these with a leading slash and the template
    // prefixed /storage, producing /storage//1589805630-harris.jpg.
    expect(MediaUrl::resolve('/1589805630-harris.jpg'))
        ->toBe('/storage/1589805630-harris.jpg');
});

it('passes an absolute url straight through', function (): void {
    expect(MediaUrl::resolve('https://example.org/a.jpg'))->toBe('https://example.org/a.jpg');
});
