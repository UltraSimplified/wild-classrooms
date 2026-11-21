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
 *
 * The artwork itself is fixed, so the seasons are applied over the top of it:
 * see Season for what each one does. The drawing therefore changes on its own
 * four times a year without anybody editing anything.
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
    public function render(Collection $spottings, ?Season $season = null): string
    {
        return $this->wrap($this->leavesFor($spottings), $season ?? $this->currentSeason());
    }

    /**
     * A tree carrying all 60 leaves: what a finished year looks like.
     *
     * The home page shows this to visitors who have no tree of their own yet —
     * a bare tree is the honest starting state but a poor advertisement.
     */
    public function renderComplete(?Season $season = null): string
    {
        $svg = '';

        foreach (self::artwork() as $categories) {
            $svg .= implode('', $categories);
        }

        return $this->wrap($svg, $season ?? $this->currentSeason());
    }

    private function currentSeason(): Season
    {
        return Season::forMonth((int) now()->format('n'));
    }

    /*
     * The trunk is drawn after the leaves, so it covers the ends of the ones
     * tucked behind it. That order is part of the artwork and is kept.
     */
    private function wrap(string $leaves, Season $season): string
    {
        $recolour = $season->recolour();

        if ($recolour !== []) {
            $leaves = str_replace(array_keys($recolour), array_values($recolour), $leaves);
        }

        $style = $season->leafStyle();

        return $this->asset('tree-head.svg')
            .($style === '' ? '' : '<style>'.$style.'</style>')
            .'<g class="wc-leaves">'.$leaves.$season->canopy($leaves).'</g>'
            .'<g class="wc-trunk">'.$this->asset('tree-tree.svg').'</g>'
            .$season->ground()
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
