<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'owner_id', 'title', 'type', 'description', 'address_line_1',
        'address_line_2', 'city', 'county', 'postal_code',
        'latitude', 'longitude', 'public', 'image',
    ];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<Unit, $this> */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Spottings belong to a unit, and units belong to a group — there is no
     * group_id on spottings, so this has to reach through units.
     *
     * @return HasManyThrough<Spotting, Unit, $this>
     */
    public function spottings(): HasManyThrough
    {
        return $this->hasManyThrough(Spotting::class, Unit::class);
    }
}
