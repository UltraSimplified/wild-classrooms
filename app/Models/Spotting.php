<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SpottingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Maize\Markable\Markable;
use Maize\Markable\Models\Like;

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

    /** @return BelongsTo<Challenge, $this> */
    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
