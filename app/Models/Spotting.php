<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SpottingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Maize\Markable\Markable;
use Maize\Markable\Models\Like;

/**
 * `spottings.likes` is an integer column holding the anonymous like count the
 * old site kept, while maize-tech/laravel-markable records a signed-in user's
 * like as a row in markable_likes. Markable registers its relation as `likes`
 * too, which collides with the column, so the relation is declared explicitly
 * here under a distinct name.
 *
 * @property-read int|null $markable_likes_count populated by withCount()
 * @property-read bool|null $liked_by_viewer populated by withExists() in the gallery
 */
class Spotting extends Model
{
    /** @use HasFactory<SpottingFactory> */
    use HasFactory;

    use Markable;

    /** @var list<class-string> */
    protected static array $marks = [
        Like::class,
    ];

    /** @var list<string> */
    protected $fillable = [
        'unit_id', 'challenge_id', 'campaign', 'notes', 'owner', 'picture',
        'exif', 'latitude', 'longitude', 'location', 'what3words',
        'gallery', 'map', 'merit', 'featured', 'likes', 'comment',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'gallery' => 'boolean',
            'map' => 'boolean',
            'merit' => 'boolean',
            'featured' => 'boolean',
            'likes' => 'integer',
        ];
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return MorphMany<Like, $this> */
    public function markableLikes(): MorphMany
    {
        return $this->morphMany(Like::class, 'markable');
    }

    /** @return BelongsTo<Challenge, $this> */
    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
