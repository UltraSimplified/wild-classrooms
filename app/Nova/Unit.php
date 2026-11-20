<?php

declare(strict_types=1);

namespace App\Nova;

use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Unit extends Resource
{
    /** @var class-string<\App\Models\Unit> */
    public static $model = \App\Models\Unit::class;

    public static $title = 'title';

    public static $group = 'Classrooms';

    /** @var array<int, string> */
    public static $search = ['title'];

    /** @var array<int, string> */
    public static $with = ['group'];

    public static function label(): string
    {
        return 'Members';
    }

    /** @return array<int, Field> */
    public function fields(NovaRequest $request): array
    {
        return [
            Heading::make('Members are smaller groups, eg family members, sixes, lodges or houses.'),
            ID::make()->sortable()->hideFromDetail()->hideFromIndex(),
            Text::make('Name', 'title')->sortable()->rules('required'),
            BelongsTo::make('Group', 'group')->sortable(),
        ];
    }
}
