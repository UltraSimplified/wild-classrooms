<?php

declare(strict_types=1);

namespace App\Nova\Metrics;

use App\Models\Group;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Trend;
use Laravel\Nova\Metrics\TrendResult;

class RegistrationsPerDay extends Trend
{
    public function calculate(NovaRequest $request): TrendResult
    {
        return $this->countByDays($request, Group::class);
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
        return 'registrations-per-day';
    }
}
