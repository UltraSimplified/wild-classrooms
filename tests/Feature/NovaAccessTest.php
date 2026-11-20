<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Events\ServingNova;

/**
 * Nova registers the viewNova gate lazily, inside Nova::serving, so the gate
 * does not exist until Nova is actually handling a request. Firing the event
 * is what boots that authorization, and is therefore what has to happen here
 * before the gate can be asserted at all.
 */
function bootNovaAuthorization(): void
{
    ServingNova::dispatch(app(), request());
}

it('admits only users listed in nova.admin_emails', function (): void {
    config(['nova.admin_emails' => ['staff@example.org']]);
    bootNovaAuthorization();

    $staff = User::factory()->create(['email' => 'staff@example.org']);
    $member = User::factory()->create(['email' => 'member@example.org']);

    expect(Gate::forUser($staff)->allows('viewNova'))->toBeTrue()
        ->and(Gate::forUser($member)->allows('viewNova'))->toBeFalse();
});

it('admits nobody when the admin list is empty', function (): void {
    // An unset NOVA_ADMIN_EMAILS must not mean "open to everyone".
    config(['nova.admin_emails' => []]);
    bootNovaAuthorization();

    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('viewNova'))->toBeFalse();
});
