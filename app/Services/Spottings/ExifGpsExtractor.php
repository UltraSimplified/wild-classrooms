<?php

declare(strict_types=1);

namespace App\Services\Spottings;

/**
 * Reads GPS coordinates out of a photo's EXIF data.
 *
 * EXIF stores each coordinate as three "numerator/denominator" rationals for
 * degrees, minutes and seconds, plus a hemisphere reference. The old code did
 * this twice, once in save() and once in update(), with the two copies drifting
 * apart: one checked GPSLatitude[1] !== '0/0' and the other checked
 * count($exif['GPSLatitude']) === 3, so the same photo could yield a location
 * on create and none on edit.
 */
final class ExifGpsExtractor
{
    /**
     * @param  array<string, mixed>|null  $exif
     * @return array{latitude: string|null, longitude: string|null}
     */
    public function extract(?array $exif): array
    {
        $none = ['latitude' => null, 'longitude' => null];

        if ($exif === null) {
            return $none;
        }

        $latitude = $this->toDecimal($exif['GPSLatitude'] ?? null);
        $longitude = $this->toDecimal($exif['GPSLongitude'] ?? null);

        if ($latitude === null || $longitude === null) {
            return $none;
        }

        // A photo with no GPS fix often still carries a zeroed-out tag.
        if ($latitude === 0.0 && $longitude === 0.0) {
            return $none;
        }

        if (($exif['GPSLatitudeRef'] ?? null) === 'S') {
            $latitude = -$latitude;
        }

        if (($exif['GPSLongitudeRef'] ?? null) === 'W') {
            $longitude = -$longitude;
        }

        return [
            'latitude' => number_format($latitude, 5, '.', ''),
            'longitude' => number_format($longitude, 5, '.', ''),
        ];
    }

    /**
     * Convert EXIF degrees/minutes/seconds rationals to a decimal degree.
     */
    private function toDecimal(mixed $parts): ?float
    {
        if (! is_array($parts) || count($parts) < 3) {
            return null;
        }

        $degrees = $this->rational($parts[0] ?? null);
        $minutes = $this->rational($parts[1] ?? null);
        $seconds = $this->rational($parts[2] ?? null);

        if ($degrees === null || $minutes === null || $seconds === null) {
            return null;
        }

        return $degrees + ($minutes / 60) + ($seconds / 3600);
    }

    /**
     * EXIF rationals arrive as "57/1", and sometimes as a plain number.
     * A zero denominator means "no reading", not a division by zero.
     */
    private function rational(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        if (! str_contains($value, '/')) {
            return is_numeric($value) ? (float) $value : null;
        }

        [$numerator, $denominator] = explode('/', $value, 2);

        if (! is_numeric($numerator) || ! is_numeric($denominator) || (float) $denominator === 0.0) {
            return null;
        }

        return (float) $numerator / (float) $denominator;
    }
}
