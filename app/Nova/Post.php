<?php

declare(strict_types=1);

namespace App\Nova;

use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;

class Post extends Resource
{
    /** @var class-string<\App\Models\Post> */
    public static $model = \App\Models\Post::class;

    public static $title = 'title';

    public static $group = 'Dig Deeper';

    /** @var array<int, string> */
    public static $search = ['title'];

    /**
     * The relation is `author` (posts.author_id), not `user` — the old
     * resource eager-loaded a relation that did not exist.
     *
     * @var array<int, string>
     */
    public static $with = ['category', 'author'];

    /** @return array<int, Field> */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable()->hideFromIndex()->hideFromDetail(),
            Text::make('Title', 'title')->sortable()->rules('required'),
            Date::make('Date posted', 'post_date')->rules('required'),
            BelongsTo::make('Category', 'category')->sortable(),
            BelongsTo::make('Challenge', 'challenge')->nullable()->sortable(),
            Image::make('Featured image', 'featured_image')->disk('public'),
            Trix::make('Teaser', 'intro')->rules('required'),
            Trix::make('Content', 'content')->rules('required'),
            BelongsTo::make('Author', 'author', User::class)->rules('required'),
        ];
    }
}
