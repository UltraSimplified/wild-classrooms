<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    /**
     * Only the owner may change a group.
     *
     * The old ProfilesController::update() loaded Group::find($request->group_id)
     * straight from request input and saved it with no ownership check at all,
     * so any signed-in user could rewrite any of the 91 groups — including its
     * members, which cascades to deleting their spottings.
     */
    public function update(User $user, Group $group): bool
    {
        return $this->owns($user, $group);
    }

    public function manageMembers(User $user, Group $group): bool
    {
        return $this->owns($user, $group);
    }

    private function owns(User $user, Group $group): bool
    {
        return $user->getKey() !== null
            && (int) $group->owner_id === (int) $user->getKey();
    }
}
