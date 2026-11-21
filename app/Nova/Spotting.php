<?php

declare(strict_types=1);

namespace App\Nova;

use App\Nova\Fields\WebpImage;
use App\Nova\Metrics\SpottingsPerDay;
use Laravel\Nova\Card;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Spotting extends Resource
{
    /** @var class-string<\App\Models\Spotting> */
    public static $model = \App\Models\Spotting::class;

    /**
     * The old resource used 'title', but spottings have no title column, so
     * every record showed up blank in search results and relation pickers.
     */
    public static $title = 'owner';

    public static $group = 'Activities';

    /** @var array<int, string> */
    public static $search = ['notes', 'owner'];

    /** @var array<int, string> */
    public static $with = ['challenge'];

    /** @return array<int, Field> */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable()->hideFromDetail()->hideFromIndex(),
            Date::make('Spotted', 'created_at')->displayUsing(
                fn ($value) => $value?->format('d M Y')
            )->sortable(),
            Boolean::make('Featured', 'featured')->sortable(),
            WebpImage::make('Picture', 'picture'),
            BelongsTo::make('Member', 'unit', Unit::class)->rules('required'),
            BelongsTo::make('Challenge', 'challenge'),
            Textarea::make('Notes'),
            Text::make('Location')->hideFromIndex(),
            Text::make('What3Words', 'what3words')->hideFromIndex(),
            Textarea::make('Comment'),
            Boolean::make('Merit', 'merit')->sortable(),
        ];
    }

    /** @return array<int, Card> */
    public function cards(NovaRequest $request): array
    {
        return [
            new SpottingsPerDay,
        ];
    }
}
