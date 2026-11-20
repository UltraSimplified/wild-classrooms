<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\Spotting;
use App\Support\MediaUrl;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ChallengeController extends Controller
{
    public function index(): Response
    {
        $month = (int) now()->format('n');

        $challenges = Challenge::query()
            ->with('category')
            ->whereNotNull('month')
            ->orderBy('month')
            ->orderBy('category_id')
            ->get();

        return Inertia::render('Challenges/Index', [
            'past' => $this->summarise($challenges->where('month', '<=', $month)),
            'upcoming' => $this->summarise($challenges->where('month', '>', $month)),
            'currentMonth' => $month,
        ]);
    }

    public function month(string $month): Response
    {
        abort_unless(ctype_digit($month), 404);

        $month = (int) $month;

        abort_unless($month >= 1 && $month <= 12, 404);

        return Inertia::render('Challenges/Month', [
            'month' => $month,
            'monthName' => date('F', mktime(0, 0, 0, $month, 1)),
            'challenges' => $this->summarise(
                Challenge::query()
                    ->with('category')
                    ->where('month', $month)
                    ->orderBy('category_id')
                    ->get()
            ),
        ]);
    }

    public function show(Challenge $challenge): Response
    {
        $challenge->load('category');

        return Inertia::render('Challenges/Show', [
            'challenge' => [
                'id' => $challenge->getKey(),
                'title' => $challenge->title,
                'plural' => $challenge->plural,
                'teaser' => $challenge->teaser,
                'description' => $challenge->description,
                'whereToFindThem' => $challenge->where_to_find_them,
                'image' => MediaUrl::resolve($challenge->image),
                'imageName' => $challenge->image_name,
                'credit' => $challenge->credit,
                'month' => $challenge->month,
                'category' => $challenge->category?->title,
            ],
            'spottings' => Spotting::query()
                ->with(['unit.group'])
                ->where('challenge_id', $challenge->getKey())
                ->whereNotNull('picture')
                ->where('gallery', true)
                ->latest()
                ->get()
                ->map(fn (Spotting $spotting): array => [
                    'id' => $spotting->getKey(),
                    'picture' => MediaUrl::resolve($spotting->picture),
                    'notes' => $spotting->notes,
                    'comment' => $spotting->comment,
                    'location' => $spotting->location,
                    'group' => $spotting->unit?->group?->title,
                    'spottedAt' => $spotting->created_at?->format('j M Y'),
                ])->all(),
        ]);
    }

    /**
     * @param  Collection<int, Challenge>  $challenges
     * @return list<array<string, mixed>>
     */
    private function summarise(Collection $challenges): array
    {
        return $challenges->values()->map(fn (Challenge $challenge): array => [
            'id' => $challenge->getKey(),
            'title' => $challenge->title,
            'slug' => str((string) $challenge->title)->slug()->value(),
            'teaser' => $challenge->teaser,
            'image' => MediaUrl::resolve($challenge->image),
            'month' => $challenge->month,
            'category' => $challenge->category?->title,
            'categoryIcon' => $challenge->category?->icon,
        ])->all();
    }
}
