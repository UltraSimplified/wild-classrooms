<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Nobody may delete themselves out of the admin area.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->hasRole('admin')
            && $user->getKey() !== $target->getKey();
    }
}
