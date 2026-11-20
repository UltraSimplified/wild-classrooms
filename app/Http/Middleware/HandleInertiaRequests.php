<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props every page gets.
     *
     * The old master.blade.php built its navigation by querying
     * Auth::user()->group->units inline in the template, on every page. That
     * work happens once, here, and the menu is driven by these props.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $group = $user?->group;

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                    'isAdmin' => $user->hasRole('admin'),
                ],
                'group' => $group === null ? null : [
                    'id' => $group->getKey(),
                    'title' => $group->title,
                    'slug' => str($group->title)->slug()->value(),
                    'members' => $group->units()
                        ->orderBy('title')
                        ->get()
                        ->map(fn ($unit): array => [
                            'id' => $unit->getKey(),
                            'title' => $unit->title,
                            'slug' => str($unit->title)->slug()->value(),
                        ])->all(),
                ],
            ],

            // The nav shows "Challenges for <current month>".
            'currentMonth' => [
                'number' => (int) now()->format('n'),
                'name' => now()->format('F'),
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
