<?php

declare(strict_types=1);

namespace App\Nova;

use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;

class Challenge extends Resource
{
    /** @var class-string<\App\Models\Challenge> */
    public static $model = \App\Models\Challenge::class;

    public static $title = 'title';

    public static $group = 'Activities';

    /** @var array<int, string> */
    public static $search = ['title', 'month', 'credit'];

    /** @var array<int, string> */
    public static $with = ['category'];

    /** @return array<int, Field> */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable()->hideFromDetail()->hideFromIndex(),
            Select::make('Month')->options([
                '0' => 'Any',
                '1' => 'January',
                '2' => 'February',
                '3' => 'March',
                '4' => 'April',
                '5' => 'May',
                '6' => 'June',
                '7' => 'July',
                '8' => 'August',
                '9' => 'September',
                '10' => 'October',
                '11' => 'November',
                '12' => 'December',
            ])->displayUsingLabels()->sortable(),
            Text::make('Title')->sortable()->rules('required'),
            Text::make('Plural')->sortable(),
            Text::make('Image name', 'image_name')->sortable(),
            BelongsTo::make('Category', 'category')->sortable(),
            Trix::make('Description'),
            Textarea::make('Where to find them', 'where_to_find_them'),
            Image::make('Image', 'image')->disk('public'),
            Text::make('Credit'),
        ];
    }
}
