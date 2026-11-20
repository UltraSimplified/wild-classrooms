<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreSpottingRequest;
use App\Http\Requests\UpdateSpottingRequest;
use App\Mail\SpottingRecorded;
use App\Models\Challenge;
use App\Models\Spotting;
use App\Models\Unit;
use App\Services\Spottings\ExifGpsExtractor;
use App\Services\Spottings\SpottingImageStore;
use App\Support\MediaUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class SpottingController extends Controller
{
    public function __construct(
        private readonly SpottingImageStore $images,
        private readonly ExifGpsExtractor $gps,
    ) {}

    public function create(?Challenge $challenge = null): Response
    {
        $group = request()->user()?->group;

        abort_if($group === null, 403, 'You need a group before you can add a spotting.');

        return Inertia::render('Spottings/Create', [
            'challenges' => $this->challengeOptions(),
            'members' => $group->units()->orderBy('title')->get()
                ->map(fn (Unit $unit): array => [
                    'id' => $unit->getKey(),
                    'title' => $unit->title,
                ])->all(),
            'selectedChallengeId' => $challenge?->getKey(),
            'campaign' => request()->query('campaign'),
        ]);
    }

    public function store(StoreSpottingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $challenge = Challenge::findOrFail($data['challenge_id']);

        $picture = null;
        $exif = null;
        $latitude = null;
        $longitude = null;

        if ($request->hasFile('picture')) {
            $stored = $this->images->store($request->file('picture'), (string) $challenge->title);
            $picture = $stored['path'];
            $exif = $stored['exif'];

            ['latitude' => $latitude, 'longitude' => $longitude] = $this->gps->extract($exif);
        }

        $spotting = Spotting::create([
            'unit_id' => $data['unit_id'],
            'challenge_id' => $data['challenge_id'],
            'campaign' => $data['campaign'] ?? null,
            'notes' => $data['notes'] ?? null,
            'location' => $data['location'] ?? null,
            'what3words' => $this->normaliseWhat3Words($data['what3words'] ?? null),
            'picture' => $picture,
            'exif' => $exif === null ? null : json_encode($exif),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'gallery' => $data['gallery'] ?? false,
            'map' => $data['map'] ?? false,
        ]);

        $this->notifyStaff($spotting, $challenge);

        return $this->backToTree($spotting)
            ->with('success', 'Your spotting has been added.');
    }

    public function edit(Spotting $spotting): Response
    {
        $spotting->load('challenge');

        return Inertia::render('Spottings/Edit', [
            'spotting' => [
                'id' => $spotting->getKey(),
                'unitId' => $spotting->unit_id,
                'challengeId' => $spotting->challenge_id,
                'notes' => $spotting->notes,
                'location' => $spotting->location,
                'what3words' => $spotting->what3words,
                'picture' => MediaUrl::resolve($spotting->picture),
                'gallery' => $spotting->gallery,
                'map' => $spotting->map,
            ],
            'challenges' => $this->challengeOptions(),
        ]);
    }

    public function update(UpdateSpottingRequest $request, Spotting $spotting): RedirectResponse
    {
        $data = $request->validated();

        if ($data['remove_picture'] ?? false) {
            $this->images->delete($spotting->picture);
            $spotting->update(['picture' => null]);

            return back()->with('success', 'Photo removed.');
        }

        $attributes = [
            'challenge_id' => $data['challenge_id'],
            'notes' => $data['notes'] ?? null,
            'location' => $data['location'] ?? null,
            'what3words' => $this->normaliseWhat3Words($data['what3words'] ?? null),
            'gallery' => $data['gallery'] ?? false,
            'map' => $data['map'] ?? false,
        ];

        if ($request->hasFile('picture')) {
            $challenge = Challenge::findOrFail($data['challenge_id']);
            $stored = $this->images->store($request->file('picture'), (string) $challenge->title);

            // Replace, don't orphan: the old code left the previous file behind.
            $this->images->delete($spotting->picture);

            $attributes['picture'] = $stored['path'];
            $attributes['exif'] = $stored['exif'] === null ? null : json_encode($stored['exif']);

            $coordinates = $this->gps->extract($stored['exif']);
            $attributes['latitude'] = $coordinates['latitude'];
            $attributes['longitude'] = $coordinates['longitude'];
        }

        $spotting->update($attributes);

        return $this->backToTree($spotting)
            ->with('success', 'Your spotting has been updated.');
    }

    public function destroy(Spotting $spotting): RedirectResponse
    {
        $this->images->delete($spotting->picture);

        $unit = $spotting->unit;
        $spotting->delete();

        if ($unit === null) {
            return redirect()->route('home');
        }

        return redirect()->route('units.tree', [
            'unit' => $unit->getKey(),
            'slug' => str((string) $unit->title)->slug()->value(),
        ])->with('success', 'Spotting deleted.');
    }

    /**
     * Stored with a leading /// so it reads as a what3words address.
     */
    private function normaliseWhat3Words(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return '///'.str_replace('///', '', $value);
    }

    private function backToTree(Spotting $spotting): RedirectResponse
    {
        $unit = $spotting->unit;

        if ($unit === null) {
            return redirect()->route('home');
        }

        return redirect()->route('units.tree', [
            'unit' => $unit->getKey(),
            'slug' => str((string) $unit->title)->slug()->value(),
        ]);
    }

    private function notifyStaff(Spotting $spotting, Challenge $challenge): void
    {
        $recipients = config('services.staff_notifications');
        $user = request()->user();
        $group = $user?->group;

        if ($recipients === [] || $user === null || $group === null) {
            return;
        }

        // There is no foreign key constraint on spottings.unit_id, so an
        // orphaned row is possible and the name may genuinely be missing.
        $spotter = Unit::whereKey($spotting->unit_id)->value('title') ?? 'a member';

        Mail::to($recipients)->send(new SpottingRecorded(
            "New spotting from {$spotter} ({$group->title})",
            $challenge,
            $spotting,
            $user,
            $group,
        ));
    }

    /** @return list<array<string, mixed>> */
    private function challengeOptions(): array
    {
        return Challenge::query()
            ->with('category')
            ->orderBy('month')
            ->orderBy('category_id')
            ->get()
            ->map(fn (Challenge $challenge): array => [
                'id' => $challenge->getKey(),
                'title' => $challenge->title,
                'month' => $challenge->month,
                'category' => $challenge->category?->title,
            ])->all();
    }
}
