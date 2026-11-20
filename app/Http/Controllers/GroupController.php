<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Unit;
use App\Support\MediaUrl;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Groups/Index', [
            'groups' => Group::query()
                ->withCount('units')
                ->orderBy('title')
                ->get()
                ->map(fn (Group $group): array => [
                    'id' => $group->getKey(),
                    'title' => $group->title,
                    'slug' => str((string) $group->title)->slug()->value(),
                    'type' => $group->type,
                    'image' => MediaUrl::resolve($group->image),
                    'members' => $group->units_count,
                ])->all(),
        ]);
    }

    /**
     * Send a signed-in user to their own woodland.
     */
    public function mine(): RedirectResponse
    {
        $group = request()->user()?->group;

        if ($group === null) {
            return redirect()->route('onboarding.group');
        }

        return redirect()->route('groups.woodland', [
            'group' => $group->getKey(),
            'slug' => str((string) $group->title)->slug()->value(),
        ]);
    }

    /**
     * A group's woodland: one tree per member.
     */
    public function woodland(Group $group): Response
    {
        $group->load(['units' => fn ($query) => $query->withCount('spottings')->orderBy('title')]);

        return Inertia::render('Groups/Woodland', [
            'group' => [
                'id' => $group->getKey(),
                'title' => $group->title,
                'slug' => str((string) $group->title)->slug()->value(),
                'type' => $group->type,
                'description' => $group->description,
                'image' => MediaUrl::resolve($group->image),
                'city' => $group->city,
                'county' => $group->county,
            ],
            'members' => $group->units
                ->map(fn (Unit $unit): array => [
                    'id' => $unit->getKey(),
                    'title' => $unit->title,
                    'slug' => str((string) $unit->title)->slug()->value(),
                    'spottings' => (int) $unit->spottings_count,
                ])->all(),
            'isMyGroup' => $this->isMyGroup($group),
        ]);
    }

    private function isMyGroup(Group $group): bool
    {
        $mine = request()->user()?->group;

        return $mine !== null && $mine->getKey() === $group->getKey();
    }
}
