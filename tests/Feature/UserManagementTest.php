<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Gate;

/*
 * User accounts are managed in Nova, by the staff Nova lets in. These go
 * through Nova's own API rather than asking the policy directly, because the
 * bug they guard against was in how Nova reads a policy. The old one defined
 * only what a front-end admin area needed. Nova refuses any ability a policy
 * leaves out — though not viewAny, which it allows — so it listed users but
 * would not open, edit or create one. Asking Laravel's gate gets viewAny
 * wrong in the other direction, which is exactly how that was first missed.
 */

it('lets Nova staff list users and open one', function (): void {
    $staff = staff();
    $user = User::factory()->create();

    $this->actingAs($staff)
        ->getJson('/nova-api/users')
        ->assertOk()
        ->assertJsonCount(2, 'resources');

    // The list showed even under the old policy; opening a user did not.
    $this->actingAs($staff)
        ->getJson("/nova-api/users/{$user->id}")
        ->assertOk();
});

it('keeps people who are not staff out of it', function (): void {
    staff();

    $this->actingAs(User::factory()->create())
        ->getJson('/nova-api/users')
        ->assertForbidden();
});

it('lets Nova staff change a user', function (): void {
    $staff = staff();
    $user = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($staff)
        ->putJson("/nova-api/users/{$user->id}", [
            'name' => 'New Name',
            'email' => $user->email,
        ])
        ->assertOk();

    expect($user->fresh()->name)->toBe('New Name');
});

it('lets Nova staff add a user', function (): void {
    $this->actingAs(staff())
        ->postJson('/nova-api/users', [
            'name' => 'Tamsin',
            'email' => 'tamsin@example.test',
            'password' => 'a-long-enough-password',
        ])
        ->assertCreated();

    expect(User::where('email', 'tamsin@example.test')->exists())->toBeTrue();
});

it('lets Nova staff delete someone else but never themselves', function (): void {
    $staff = staff();
    $other = User::factory()->create();

    $this->actingAs($staff)
        ->deleteJson('/nova-api/users', ['resources' => [$staff->id, $other->id]])
        ->assertOk();

    expect(User::find($other->id))->toBeNull()
        ->and(User::find($staff->id))->not->toBeNull();
});

it('treats everyone signed in as staff locally, as Nova itself does', function (): void {
    // Nova admits anyone in local development regardless of the gate. If the
    // gate disagreed, the Users screen alone would vanish there.
    $this->app['env'] = 'local';
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('viewNova'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('viewAny', User::class))->toBeTrue();
});

it('has no front-end user admin any more', function (): void {
    $this->actingAs(staff())->get('/admin/users')->assertNotFound();
});
