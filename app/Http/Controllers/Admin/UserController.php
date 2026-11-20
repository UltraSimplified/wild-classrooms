<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->with('roles')
                ->orderBy('name')
                ->paginate(50)
                ->through(fn (User $user): array => [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->all(),
                    'joined' => $user->created_at?->format('j M Y'),
                ]),
        ]);
    }

    public function edit(User $user): Response
    {
        $user->load('roles');

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'roleIds' => $user->roles->pluck('id')->all(),
            ],
            'roles' => Role::query()->orderBy('name')->get()
                ->map(fn (Role $role): array => [
                    'id' => $role->getKey(),
                    'name' => $role->name,
                ])->all(),
        ]);
    }

    public function update(User $user): RedirectResponse
    {
        $data = request()->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:254', Rule::unique('users', 'email')->ignore($user->getKey())],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $user->roles()->sync($data['roles'] ?? []);

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name} has been updated.");
    }

    public function destroy(User $user): RedirectResponse
    {
        // An admin must not delete their own account from here and lock
        // everyone out; the policy refuses it.
        Gate::authorize('delete', $user);

        $user->roles()->detach();
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted.');
    }
}
