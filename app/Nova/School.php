<?php

declare(strict_types=1);

namespace App\Nova;

use Illuminate\Http\Resources\MergeValue;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * Hidden from navigation and unused in production (0 rows). Kept so the data
 * model stays complete; a candidate for removal once confirmed dead.
 */
class School extends Resource
{
    /** @var class-string<\App\Models\School> */
    public static $model = \App\Models\School::class;

    public static $displayInNavigation = false;

    public static $title = 'school';

    public static $group = 'Groups';

    /** @var array<int, string> */
    public static $search = ['school'];

    /** @return array<int, Field|MergeValue> */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable()->hideFromDetail(),
            Text::make('School')->sortable()->rules('required'),
            Image::make('Logo', 'logo')->disk('public'),
            Image::make('Featured image', 'featured_image')->disk('public'),
            Trix::make('Statement', 'statement')->rules('required'),
            Text::make('Head teacher', 'head')->rules('required'),
            // Nova has no first-party colour field; a hex value as text keeps
            // this working without pulling in a third-party field package.
            Text::make('Accent colour', 'accent_colour')
                ->rules('required', 'regex:/^#[0-9a-fA-F]{6}$/')
                ->help('Hex value, e.g. #21B978'),
            $this->addressFields(),
        ];
    }

    protected function addressFields(): MergeValue
    {
        return $this->merge([
            Text::make('Address', 'address_line_1')->rules('required'),
            Text::make('City', 'city')->hideFromIndex(),
            Text::make('Postal code', 'postal_code'),
            Text::make('Latitude')->hideFromIndex()->hideFromDetail(),
            Text::make('Longitude')->hideFromIndex()->hideFromDetail(),
        ]);
    }
}
