<?php

use App\Enums\CashRegisterStatus;
use App\Models\CashRegisterSession;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

function overrideUserWithRole(string $role, string $password = 'secret-pass'): User
{
    $user = User::factory()->create(['password' => $password]);
    $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));

    return $user;
}

function overrideOpenSession(User $user): CashRegisterSession
{
    return CashRegisterSession::create([
        'user_id' => $user->id,
        'opening_amount' => 1000,
        'opened_at' => now(),
        'status' => CashRegisterStatus::Open,
    ]);
}

it('blocks a cashier closing at zero without manager approval', function () {
    $cashier = overrideUserWithRole('cashier');
    $session = overrideOpenSession($cashier);

    actingAs($cashier);
    postJson('/pos/register/close', ['closing_amount' => 0, 'confirm_empty_drawer' => true])
        ->assertStatus(422)
        ->assertJson(['requires_manager_approval' => true]);

    expect($session->fresh()->status)->toBe(CashRegisterStatus::Open);
});

it('rejects wrong or non-manager credentials', function () {
    $cashier = overrideUserWithRole('cashier');
    $otherCashier = overrideUserWithRole('cashier');
    $manager = overrideUserWithRole('admin');
    $session = overrideOpenSession($cashier);

    actingAs($cashier);
    postJson('/pos/register/close', [
        'closing_amount' => 0,
        'confirm_empty_drawer' => true,
        'manager_email' => $manager->email,
        'manager_password' => 'wrong',
    ])->assertStatus(422);

    postJson('/pos/register/close', [
        'closing_amount' => 0,
        'confirm_empty_drawer' => true,
        'manager_email' => $otherCashier->email,
        'manager_password' => 'secret-pass',
    ])->assertStatus(422);

    expect($session->fresh()->status)->toBe(CashRegisterStatus::Open);
});

it('closes at zero when a manager approves and records the approver', function () {
    $cashier = overrideUserWithRole('cashier');
    $manager = overrideUserWithRole('admin');
    $session = overrideOpenSession($cashier);

    actingAs($cashier);
    postJson('/pos/register/close', [
        'closing_amount' => 0,
        'confirm_empty_drawer' => true,
        'manager_email' => $manager->email,
        'manager_password' => 'secret-pass',
    ])->assertOk()->assertJson(['success' => true]);

    $session->refresh();
    expect($session->status)->toBe(CashRegisterStatus::Closed)
        ->and($session->override_approved_by)->toBe($manager->id)
        ->and($session->override_approved_at)->not->toBeNull();
});

it('lets a manager approve their own zero close', function () {
    $manager = overrideUserWithRole('admin');
    $session = overrideOpenSession($manager);

    actingAs($manager);
    postJson('/pos/register/close', ['closing_amount' => 0, 'confirm_empty_drawer' => true])
        ->assertOk();

    expect($session->fresh()->override_approved_by)->toBe($manager->id);
});

it('does not need approval for a normal counted close', function () {
    $cashier = overrideUserWithRole('cashier');
    $session = overrideOpenSession($cashier);

    actingAs($cashier);
    postJson('/pos/register/close', ['closing_amount' => 1000])->assertOk();

    expect($session->fresh()->override_approved_by)->toBeNull();
});
