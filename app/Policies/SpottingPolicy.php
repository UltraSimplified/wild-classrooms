<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Spotting;
use App\Models\Unit;
use App\Models\User;

class SpottingPolicy
{
    /**
     * A spotting may only be filed against a member of your own group.
     *
     * The old SpottingsController::save() took unit_id from request input with
     * no check, so any signed-in user could attach a spotting — and a photo —
     * to any of the 266 members of any group.
     */
    public function createFor(User $user, Unit $unit): bool
    {
        return $this->ownsUnit($user, $unit);
    }

    public function update(User $user, Spotting $spotting): bool
    {
        $unit = $spotting->unit;

        return $unit !== null && $this->ownsUnit($user, $unit);
    }

    public function delete(User $user, Spotting $spotting): bool
    {
        return $this->update($user, $spotting);
    }

    private function ownsUnit(User $user, Unit $unit): bool
    {
        $group = $user->group;

        return $group !== null
            && (int) $unit->group_id === (int) $group->getKey();
    }
}
