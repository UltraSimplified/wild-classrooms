<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\Group;
use App\Models\Spotting;
use App\Support\MediaSize;
use App\Support\MediaUrl;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $month = (int) now()->format('n');

        return Inertia::render('Home', [
            // Challenges up to and including the current month: the ones a
            // group could already have been out and spotted.
            'challenges' => Challenge::query()
                ->with('category')
                ->whereNotNull('month')
                ->where('month', '<=', $month)
                ->orderBy('month')
                ->orderBy('category_id')
                ->get()
                ->map(fn (Challenge $challenge): array => [
                    'id' => $challenge->getKey(),
                    'title' => $challenge->title,
                    'slug' => str((string) $challenge->title)->slug()->value(),
                    'month' => $challenge->month,
                    'image' => MediaUrl::resolve($challenge->image),
                    'category' => $challenge->category?->title,
                ])->all(),

            'featured' => Spotting::query()
                ->with(['challenge', 'unit.group'])
                ->where('featured', true)
                ->whereNotNull('picture')
                ->latest()
                ->limit(30)
                ->get()
                ->map(fn (Spotting $spotting): array => [
                    'id' => $spotting->getKey(),
                    'picture' => MediaUrl::resolve($spotting->picture),
                    'size' => MediaSize::of($spotting->picture),
                    'notes' => $spotting->notes,
                    'comment' => $spotting->comment,
                    'challenge' => $spotting->challenge?->title,
                    'challengeId' => $spotting->challenge_id,
                    'group' => $spotting->unit?->group?->title,
                ])->all(),

            // The home page plots every public group on a map.
            'mapGroups' => Group::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get()
                ->map(fn (Group $group): array => [
                    'id' => $group->getKey(),
                    'title' => $group->title,
                    'slug' => str((string) $group->title)->slug()->value(),
                    'latitude' => $group->latitude,
                    'longitude' => $group->longitude,
                ])->all(),
        ]);
    }
}
