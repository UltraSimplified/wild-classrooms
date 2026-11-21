<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Who may manage user accounts. That is done in Nova, by the staff Nova lets
 * in, so every ability defers to the viewNova gate.
 *
 * Nova applies a model's policy by itself, and refuses any ability the policy
 * leaves out — except viewAny, which it allows when the method is missing. An
 * earlier version, written for a front-end admin area, defined only `manage`
 * and `delete`. Nova therefore listed users but would not let anyone open,
 * edit or create one. Each ability Nova asks about is spelt out here for that
 * reason, even where the answer is the same.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, User $target): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, User $target): bool
    {
        return $this->isStaff($user);
    }

    /** Nobody may delete their own account from Nova. */
    public function delete(User $user, User $target): bool
    {
        return $this->isStaff($user) && $user->getKey() !== $target->getKey();
    }

    private function isStaff(User $user): bool
    {
        return Gate::forUser($user)->allows('viewNova');
    }
}
