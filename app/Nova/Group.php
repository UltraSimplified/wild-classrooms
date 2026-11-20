<?php

declare(strict_types=1);

namespace App\Nova;

use Illuminate\Http\Resources\MergeValue;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @mixin \App\Models\Group
 */
class Group extends Resource
{
    /** @var class-string<\App\Models\Group> */
    public static $model = \App\Models\Group::class;

    public static $title = 'title';

    public static $group = 'Classrooms';

    /** @var array<int, string> */
    public static $search = ['title'];

    /** @return array<int, Field|MergeValue> */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable()->hideFromDetail(),
            Text::make('Title', 'title')->sortable()->rules('required'),
            Select::make('Type', 'type')->options([
                'Group' => 'Group',
                'Family' => 'Family',
                'School' => 'School',
                'Other' => 'Other',
            ])->displayUsingLabels(),
            Image::make('Image', 'image')->disk('public'),
            Trix::make('Description', 'description'),
            Boolean::make('Public', 'public'),
            $this->addressFields(),
        ];
    }

    /**
     * Nova 5 removed the Place field along with Algolia Places, which shut
     * down, so the address is plain text now. Latitude and longitude are set
     * by hand or by geocoding rather than by an autocomplete.
     */
    protected function addressFields(): MergeValue
    {
        return $this->merge([
            Text::make('Address', 'address_line_1')->rules('required')->hideFromIndex(),
            Text::make('Address line 2', 'address_line_2')->hideFromIndex(),
            Text::make('Town', 'city')->hideFromIndex(),
            Text::make('County', 'county'),
            Text::make('Postcode', 'postal_code')->hideFromIndex(),
            Text::make('Located', fn (): string => $this->latitude ? 'Yes' : '—')
                ->exceptOnForms(),
            Text::make('Latitude')->hideFromIndex()->hideFromDetail(),
            Text::make('Longitude')->hideFromIndex()->hideFromDetail(),
        ]);
    }
}
