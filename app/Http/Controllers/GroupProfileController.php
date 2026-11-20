<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GroupRequest;
use App\Models\Group;
use App\Models\Unit;
use App\Services\Groups\GroupImageStore;
use App\Services\Groups\MemberSync;
use App\Support\MediaUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Editing your own group and its members.
 */
class GroupProfileController extends Controller
{
    public function __construct(
        private readonly GroupImageStore $images,
        private readonly MemberSync $members,
    ) {}

    public function edit(): RedirectResponse|Response
    {
        $group = request()->user()?->group;

        if ($group === null) {
            return redirect()->route('onboarding.group');
        }

        return Inertia::render('Profile/EditGroup', [
            'group' => [
                'id' => $group->getKey(),
                'title' => $group->title,
                'type' => $group->type,
                'description' => $group->description,
                'address_line_1' => $group->address_line_1,
                'address_line_2' => $group->address_line_2,
                'city' => $group->city,
                'county' => $group->county,
                'postal_code' => $group->postal_code,
                'public' => (bool) $group->public,
                'image' => MediaUrl::resolve($group->image),
            ],
            'members' => $group->units()->withCount('spottings')->orderBy('id')->get()
                ->map(fn (Unit $unit): array => [
                    'id' => $unit->getKey(),
                    'title' => $unit->title,
                    'spottings' => (int) $unit->spottings_count,
                ])->all(),
        ]);
    }

    public function update(GroupRequest $request): RedirectResponse
    {
        // The group comes from the signed-in user, never from the form. The
        // old controller did Group::find($request->group_id) with no check,
        // so any user could rewrite any group.
        $group = $request->user()?->group;

        abort_if($group === null, 403);

        Gate::authorize('update', $group);

        $data = $request->validated();

        $group->fill([
            'title' => $data['title'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'] ?? null,
            'city' => $data['city'],
            'county' => $data['county'] ?? null,
            'postal_code' => $data['postal_code'],
            'public' => $data['public'] ? '1' : '0',
        ]);

        if ($request->hasFile('picture')) {
            $group->image = $this->images->store($request->file('picture'), $data['title']);
        }

        $group->save();

        if (array_key_exists('members', $data)) {
            $this->members->sync($group, $data['members']);
        }

        return back()->with('success', 'Your group has been updated.');
    }
}
