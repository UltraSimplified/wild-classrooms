<?php

declare(strict_types=1);

namespace App\Nova\Metrics;

use App\Models\Group;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class GroupTypes extends Partition
{
    public function calculate(NovaRequest $request): PartitionResult
    {
        return $this->count($request, Group::class, 'type');
    }

    public function cacheFor(): \DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    public function uriKey(): string
    {
        return 'group-types';
    }
}
