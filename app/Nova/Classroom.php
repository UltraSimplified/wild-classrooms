<?php

declare(strict_types=1);

namespace App\Nova;

use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * Hidden from navigation and all but unused in production (5 rows).
 */
class Classroom extends Resource
{
    /** @var class-string<\App\Models\Classroom> */
    public static $model = \App\Models\Classroom::class;

    public static $displayInNavigation = false;

    // The old resource declared $title twice ('teacher' then 'school'), so the
    // second silently won and pointed at a column classrooms does not have.
    public static $title = 'teacher';

    public static $group = 'Groups';

    /** @var array<int, string> */
    public static $search = ['teacher'];

    /** @var array<int, string> */
    public static $with = ['school'];

    /** @return array<int, Field> */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable()->hideFromDetail()->hideFromIndex(),
            BelongsTo::make('School', 'school')->sortable(),
            Text::make('Teacher')->sortable()->rules('required'),
            Text::make('Year')->rules('required', 'numeric'),
            Text::make('Key stage', 'key_stage')->rules('required'),
            Text::make('Intake year', 'intake_year')->rules('required', 'numeric'),
        ];
    }
}
