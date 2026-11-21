<?php

declare(strict_types=1);

use App\Services\Spottings\ExifGpsExtractor;

beforeEach(function (): void {
    $this->gps = new ExifGpsExtractor;
});

it('returns nothing when there is no exif', function (): void {
    expect($this->gps->extract(null))->toBe(['latitude' => null, 'longitude' => null])
        ->and($this->gps->extract([]))->toBe(['latitude' => null, 'longitude' => null]);
});

it('converts degrees, minutes and seconds to decimal degrees', function (): void {
    // Stroud: 51° 44' 40" N, 2° 13' 1" W
    $result = $this->gps->extract([
        'GPSLatitude' => ['51/1', '44/1', '40/1'],
        'GPSLatitudeRef' => 'N',
        'GPSLongitude' => ['2/1', '13/1', '1/1'],
        'GPSLongitudeRef' => 'W',
    ]);

    expect((float) $result['latitude'])->toBeGreaterThan(51.74)
        ->and((float) $result['latitude'])->toBeLessThan(51.75)
        // West is negative.
        ->and((float) $result['longitude'])->toBeLessThan(-2.21)
        ->and((float) $result['longitude'])->toBeGreaterThan(-2.22);
});

it('makes southern latitudes negative', function (): void {
    $result = $this->gps->extract([
        'GPSLatitude' => ['33/1', '55/1', '0/1'],
        'GPSLatitudeRef' => 'S',
        'GPSLongitude' => ['18/1', '25/1', '0/1'],
        'GPSLongitudeRef' => 'E',
    ]);

    expect((float) $result['latitude'])->toBeLessThan(0)
        ->and((float) $result['longitude'])->toBeGreaterThan(0);
});

it('survives a zero denominator rather than dividing by zero', function (): void {
    // A camera with no GPS fix writes 0/0 into the tag. The old code divided
    // by it directly.
    expect($this->gps->extract([
        'GPSLatitude' => ['0/0', '0/0', '0/0'],
        'GPSLongitude' => ['0/0', '0/0', '0/0'],
    ]))->toBe(['latitude' => null, 'longitude' => null]);
});

it('treats a zeroed-out fix as no location', function (): void {
    expect($this->gps->extract([
        'GPSLatitude' => ['0/1', '0/1', '0/1'],
        'GPSLatitudeRef' => 'N',
        'GPSLongitude' => ['0/1', '0/1', '0/1'],
        'GPSLongitudeRef' => 'E',
    ]))->toBe(['latitude' => null, 'longitude' => null]);
});

it('ignores a truncated or malformed gps tag', function (): void {
    expect($this->gps->extract(['GPSLatitude' => ['51/1']]))
        ->toBe(['latitude' => null, 'longitude' => null])
        ->and($this->gps->extract(['GPSLatitude' => 'not an array']))
        ->toBe(['latitude' => null, 'longitude' => null]);
});

it('accepts plain numbers as well as rationals', function (): void {
    $result = $this->gps->extract([
        'GPSLatitude' => [51, 44, 40],
        'GPSLatitudeRef' => 'N',
        'GPSLongitude' => [2, 13, 1],
        'GPSLongitudeRef' => 'W',
    ]);

    expect($result['latitude'])->not->toBeNull()
        ->and((float) $result['latitude'])->toBeGreaterThan(51.74);
});

it('finds the GPS tags in EXIF grouped by section, as Intervention reads it', function (): void {
    $result = $this->gps->extract([
        'IFD0' => ['Make' => 'Phone'],
        'GPS' => [
            'GPSLatitude' => ['51/1', '44/1', '40/1'],
            'GPSLatitudeRef' => 'N',
            'GPSLongitude' => ['2/1', '13/1', '1/1'],
            'GPSLongitudeRef' => 'W',
        ],
    ]);

    expect((float) $result['latitude'])->toBeGreaterThan(51.74)->toBeLessThan(51.75)
        ->and((float) $result['longitude'])->toBeGreaterThan(-2.22)->toBeLessThan(-2.21);
});
