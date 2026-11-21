<?php

declare(strict_types=1);

use App\Services\Trees\Season;

it('puts each month in its season', function (int $month, Season $expected): void {
    expect(Season::forMonth($month))->toBe($expected);
})->with([
    'January' => [1, Season::Winter],
    'February' => [2, Season::Winter],
    'March' => [3, Season::Spring],
    'May' => [5, Season::Spring],
    'June' => [6, Season::Summer],
    'August' => [8, Season::Summer],
    'September' => [9, Season::Autumn],
    'November' => [11, Season::Autumn],
    'December' => [12, Season::Winter],
]);

it('treats a month outside the year as winter rather than failing', function (): void {
    // Months reach this from a nullable column elsewhere in the app. A bad
    // value should still draw a tree rather than throw.
    expect(Season::forMonth(0))->toBe(Season::Winter)
        ->and(Season::forMonth(13))->toBe(Season::Winter);
});

it('only repaints the leaves, never the outline, outside autumn', function (): void {
    // The outline warms up with an orange leaf because a deep green edge on
    // an orange leaf reads as a mistake. Winter keeps the green edge.
    expect(Season::Autumn->recolour())->toHaveKey('#0a3309')
        ->and(Season::Winter->recolour())->not->toHaveKey('#0a3309')
        ->and(Season::Spring->recolour())->toBe([])
        ->and(Season::Summer->recolour())->toBe([]);
});
