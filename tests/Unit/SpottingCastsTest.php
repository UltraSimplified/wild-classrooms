<?php

declare(strict_types=1);

use App\Models\Spotting;

it('casts the tinyint flags to booleans', function (): void {
    // `featured` is tinyint(4) in production while the others are tinyint(1);
    // all four should still read back as real booleans, not 1/0.
    $spotting = Spotting::factory()->create([
        'gallery' => true,
        'map' => false,
        'merit' => true,
        'featured' => false,
        'likes' => 7,
    ]);

    $fresh = $spotting->fresh();

    expect($fresh->gallery)->toBeTrue()
        ->and($fresh->map)->toBeFalse()
        ->and($fresh->merit)->toBeTrue()
        ->and($fresh->featured)->toBeFalse()
        ->and($fresh->likes)->toBe(7);
});
