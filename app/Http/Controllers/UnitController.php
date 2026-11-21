<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\Spotting;
use App\Models\Unit;
use App\Services\Trees\TreeRenderer;
use App\Support\MediaSize;
use App\Support\MediaUrl;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A member's own page: their tree, and the challenges they can tick off.
 */
class UnitController extends Controller
{
    public function __construct(private readonly TreeRenderer $trees) {}

    public function tree(Unit $unit, ?string $slug = null, ?string $month = null): Response
    {
        $unit->load('group');

        $month = $this->resolveMonth($month);
        $spottings = $this->spottingsFor($unit);

        return Inertia::render('Units/Tree', [
            'unit' => $this->unitProps($unit),
            'group' => [
                'id' => $unit->group?->getKey(),
                'title' => $unit->group?->title,
                'slug' => str((string) $unit->group?->title)->slug()->value(),
            ],
            'month' => $month,
            'monthName' => date('F', mktime(0, 0, 0, $month, 1)),
            'challenges' => $this->challengesForMonth($month),
            // December used to ask for month 13 and get nothing back.
            'nextMonth' => $month % 12 + 1,
            'nextChallenges' => $this->challengesForMonth($month % 12 + 1),
            'spotted' => $this->trees->spottedPairs($spottings),
            'spottings' => $this->spottingProps($spottings),
            'isMyGroup' => $this->isMyGroup($unit),
        ]);
    }

    public function challenges(Unit $unit, ?string $month = null): Response
    {
        $unit->load('group');

        $month = $this->resolveMonth($month);
        $spottings = $this->spottingsFor($unit);

        return Inertia::render('Units/Challenges', [
            'unit' => $this->unitProps($unit),
            'group' => [
                'id' => $unit->group?->getKey(),
                'title' => $unit->group?->title,
                'slug' => str((string) $unit->group?->title)->slug()->value(),
            ],
            'month' => $month,
            'monthName' => date('F', mktime(0, 0, 0, $month, 1)),
            'challenges' => $this->challengesForMonth($month),
            'spotted' => $this->trees->spottedPairs($spottings),
            'isMyGroup' => $this->isMyGroup($unit),
        ]);
    }

    private function resolveMonth(?string $month): int
    {
        if ($month === null || ! ctype_digit($month)) {
            return (int) now()->format('n');
        }

        $value = (int) $month;

        return ($value >= 1 && $value <= 12) ? $value : (int) now()->format('n');
    }

    /** @return Collection<int, Spotting> */
    private function spottingsFor(Unit $unit): Collection
    {
        return Spotting::query()
            ->with(['challenge.category'])
            ->where('unit_id', $unit->getKey())
            ->orderByDesc('id')
            ->get();
    }

    /** @return array<string, mixed> */
    private function unitProps(Unit $unit): array
    {
        return [
            'id' => $unit->getKey(),
            'title' => $unit->title,
            'slug' => str((string) $unit->title)->slug()->value(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function challengesForMonth(int $month): array
    {
        return Challenge::query()
            ->with('category')
            ->where('month', $month)
            ->orderBy('category_id')
            ->get()
            ->map(fn (Challenge $challenge): array => [
                'id' => $challenge->getKey(),
                'title' => $challenge->title,
                'slug' => str((string) $challenge->title)->slug()->value(),
                'teaser' => $challenge->teaser,
                'image' => MediaUrl::resolve($challenge->image),
                'category' => $challenge->category?->title,
                'categoryIcon' => $challenge->category?->icon,
            ])->all();
    }

    /**
     * @param  Collection<int, Spotting>  $spottings
     * @return list<array<string, mixed>>
     */
    private function spottingProps(Collection $spottings): array
    {
        return $spottings->values()->map(fn (Spotting $spotting): array => [
            'id' => $spotting->getKey(),
            'picture' => MediaUrl::resolve($spotting->picture),
            'size' => MediaSize::of($spotting->picture),
            'notes' => $spotting->notes,
            'comment' => $spotting->comment,
            'location' => $spotting->location,
            'what3words' => $spotting->what3words,
            'merit' => $spotting->merit,
            'challenge' => $spotting->challenge?->title,
            'challengeId' => $spotting->challenge_id,
            'spottedAt' => $spotting->created_at?->format('j M Y'),
        ])->all();
    }

    private function isMyGroup(Unit $unit): bool
    {
        $group = request()->user()?->group;

        return $group !== null && (int) $unit->group_id === (int) $group->getKey();
    }
}
