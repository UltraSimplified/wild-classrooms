<?php

declare(strict_types=1);

namespace App\Services\Trees;

use App\Models\Spotting;
use Illuminate\Support\Collection;

/**
 * Draws a member's woodland tree.
 *
 * The tree gains one leaf for each month-and-category the member has spotted
 * something in: 12 months x 5 categories = 60 leaves, each a fixed piece of
 * artwork positioned on the trunk. The artwork lives in resources/svg and was
 * lifted verbatim from the old controller, which held all of it inline.
 */
final class TreeRenderer
{
    /** @var array<string, array<string, string>>|null */
    private static ?array $leaves = null;

    /**
     * Render the tree for a unit's spottings.
     *
     * @param  Collection<int, Spotting>  $spottings
     */
    public function render(Collection $spottings): string
    {
        return $this->wrap($this->leavesFor($spottings));
    }

    /**
     * A tree carrying all 60 leaves: what a finished year looks like.
     *
     * The home page shows this to visitors who have no tree of their own yet —
     * a bare tree is the honest starting state but a poor advertisement.
     */
    public function renderComplete(): string
    {
        $svg = '';

        foreach (self::artwork() as $categories) {
            $svg .= implode('', $categories);
        }

        return $this->wrap($svg);
    }

    private function wrap(string $leaves): string
    {
        return $this->asset('tree-head.svg')
            .$leaves
            .$this->asset('tree-tree.svg')
            .$this->asset('tree-foot.svg');
    }

    /**
     * Which month/category pairs this member has spotted something in.
     *
     * @param  Collection<int, Spotting>  $spottings
     * @return array<string, array<string, true>>
     */
    public function spottedPairs(Collection $spottings): array
    {
        $pairs = [];

        foreach ($spottings as $spotting) {
            $challenge = $spotting->challenge;

            // The "Wild spotting" challenge has no month. The old code fed a
            // null month to strtotime, which failed and fell back to the epoch,
            // so every wild spotting grew a January leaf it had not earned.
            if ($challenge === null || $challenge->month === null || $challenge->month < 1 || $challenge->month > 12) {
                continue;
            }

            $category = $challenge->category;

            if ($category === null) {
                continue;
            }

            $month = $this->monthAbbreviation($challenge->month);

            $pairs[$month][(string) $category->title] = true;
        }

        return $pairs;
    }

    /**
     * @param  Collection<int, Spotting>  $spottings
     */
    private function leavesFor(Collection $spottings): string
    {
        $artwork = self::artwork();
        $svg = '';

        foreach ($this->spottedPairs($spottings) as $month => $categories) {
            foreach (array_keys($categories) as $category) {
                // A category with no leaf slot (currently "Wild spotting")
                // simply has nothing to draw.
                $svg .= $artwork[$month][$category] ?? '';
            }
        }

        return $svg;
    }

    private function monthAbbreviation(int $month): string
    {
        return date('M', mktime(0, 0, 0, $month, 1));
    }

    /** @return array<string, array<string, string>> */
    private static function artwork(): array
    {
        return self::$leaves ??= require resource_path('svg/tree-leaves.php');
    }

    private function asset(string $file): string
    {
        return (string) file_get_contents(resource_path('svg/'.$file));
    }
}
