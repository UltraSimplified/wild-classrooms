<?php

declare(strict_types=1);

namespace App\Nova;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Category extends Resource
{
    /** @var class-string<\App\Models\Category> */
    public static $model = \App\Models\Category::class;

    public static $title = 'title';

    public static $group = 'Settings';

    /** @var array<int, string> */
    public static $search = ['title'];

    /** @return array<int, Field> */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->hideFromIndex()->hideFromDetail(),
            Text::make('Title')->sortable()->rules('required'),
            Image::make('Image', 'image')->disk('public'),
        ];
    }
}
