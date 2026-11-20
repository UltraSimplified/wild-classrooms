<?php

declare(strict_types=1);

namespace App\Nova\Metrics;

use App\Models\Spotting;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

class SpottingsPerDay extends Trend
{
    public function calculate(NovaRequest $request): TrendResult
    {
        return $this->countByDays($request, Spotting::class);
    }

    /** @return array<int, string> */
    public function ranges(): array
    {
        return [
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
        ];
    }

    public function cacheFor(): \DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    public function uriKey(): string
    {
        return 'spottings-per-day';
    }
}
