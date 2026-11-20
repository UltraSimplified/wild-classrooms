<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GroupRequest;
use App\Mail\GroupRegistered;
use App\Mail\RegistrationComplete;
use App\Models\Group;
use App\Services\Groups\GroupImageStore;
use App\Services\Groups\MemberSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Signing a new group up: group details, then its members.
 */
class OnboardingController extends Controller
{
    public function __construct(
        private readonly GroupImageStore $images,
        private readonly MemberSync $members,
    ) {}

    public function group(): RedirectResponse|Response
    {
        $existing = request()->user()?->group;

        // One group per user: groups.owner_id is where the link lives.
        if ($existing !== null) {
            return redirect()->route('groups.woodland', [
                'group' => $existing->getKey(),
                'slug' => str((string) $existing->title)->slug()->value(),
            ]);
        }

        return Inertia::render('Onboarding/Group');
    }

    public function storeGroup(GroupRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user === null, 403);
        abort_if($user->group !== null, 409, 'You already have a group.');

        $data = $request->validated();

        $group = new Group([
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

        $group->owner_id = $user->getKey();

        if ($request->hasFile('picture')) {
            $group->image = $this->images->store($request->file('picture'), $data['title']);
        }

        $group->save();

        $this->notifyStaff($group, $request);

        return redirect()->route('onboarding.members');
    }

    public function members(): RedirectResponse|Response
    {
        $group = request()->user()?->group;

        if ($group === null) {
            return redirect()->route('onboarding.group');
        }

        return Inertia::render('Onboarding/Members', [
            'group' => [
                'id' => $group->getKey(),
                'title' => $group->title,
            ],
        ]);
    }

    public function storeMembers(GroupRequest $request): RedirectResponse
    {
        $user = $request->user();
        $group = $user?->group;

        // The old version took group_id from the form, so anyone could add
        // members to anyone else's group. It comes from the session now.
        abort_if($user === null || $group === null, 403);

        $this->members->sync($group, $request->validated()['members'] ?? []);

        Mail::to($user->email)->send(new RegistrationComplete(
            'Welcome to Wild Classrooms!',
            $group,
            $user,
        ));

        return redirect()->route('groups.woodland', [
            'group' => $group->getKey(),
            'slug' => str((string) $group->title)->slug()->value(),
        ])->with('success', 'Your woodland is ready.');
    }

    private function notifyStaff(Group $group, GroupRequest $request): void
    {
        $recipients = config('services.staff_notifications');
        $user = $request->user();

        if ($recipients === [] || $user === null) {
            return;
        }

        Mail::to($recipients)->send(new GroupRegistered('New registration', $group, $user));
    }
}
