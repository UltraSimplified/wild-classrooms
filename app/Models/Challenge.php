<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ChallengeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    /** @use HasFactory<ChallengeFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'category_id', 'month', 'title', 'plural', 'image_name',
        'image', 'credit', 'teaser', 'description', 'where_to_find_them',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'month' => 'integer',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<Spotting, $this> */
    public function spottings(): HasMany
    {
        return $this->hasMany(Spotting::class);
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
