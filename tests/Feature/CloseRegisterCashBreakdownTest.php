<?php

use App\Enums\CashRegisterStatus;
use App\Models\CashRegisterSession;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

function breakdownCashier(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']));

    return $user;
}

function breakdownOpenSession(User $user): CashRegisterSession
{
    return CashRegisterSession::create([
        'user_id' => $user->id,
        'opening_amount' => 1000,
        'opened_at' => now(),
        'status' => CashRegisterStatus::Open,
    ]);
}

it('saves the denomination count and uses its total as the closing amount', function () {
    $cashier = breakdownCashier();
    $session = breakdownOpenSession($cashier);

    actingAs($cashier);
    postJson('/pos/register/close', [
        // The client total is ignored when a breakdown is sent.
        'closing_amount' => 1,
        'cash_breakdown' => [
            '1000' => 0, '500' => 2, '200' => 10, '50' => 10, '20' => 10,
            '10' => 10, '5' => 12, '1' => 12, '0.25' => 4,
        ],
    ])->assertOk()->assertJson(['success' => true]);

    $session->refresh();

    // 1000 + 2000 + 500 + 200 + 100 + 60 + 12 + 1 = 3873
    expect((float) $session->closing_amount)->toBe(3873.0)
        ->and($session->cash_breakdown)->toBe([
            '500' => 2, '200' => 10, '50' => 10, '20' => 10,
            '10' => 10, '5' => 12, '1' => 12, '0.25' => 4,
        ])
        ->and((float) $session->discrepancy)->toBe(2873.0)
        ->and($session->status)->toBe(CashRegisterStatus::Closed);

    get(route('pos.register.sales-report', $session->id))->assertOk();

    $html = view('pos.register-sales-report', [
        'session' => $session,
        'sales' => collect(),
        'settlements' => collect(),
        'nonCashCollected' => 0,
    ])->render();

    expect($html)->toContain('Cash Breakdown')->toContain('₱3,873.00');
});

it('still closes with a plain amount when no breakdown is sent', function () {
    $cashier = breakdownCashier();
    $session = breakdownOpenSession($cashier);

    actingAs($cashier);
    postJson('/pos/register/close', ['closing_amount' => 1000])->assertOk();

    $session->refresh();

    expect((float) $session->closing_amount)->toBe(1000.0)
        ->and($session->cash_breakdown)->toBeNull();
});

it('rejects negative or fractional piece counts', function () {
    $cashier = breakdownCashier();
    $session = breakdownOpenSession($cashier);

    actingAs($cashier);
    postJson('/pos/register/close', ['closing_amount' => 0, 'cash_breakdown' => ['500' => -1]])->assertStatus(422);
    postJson('/pos/register/close', ['closing_amount' => 0, 'cash_breakdown' => ['500' => 1.5]])->assertStatus(422);

    expect($session->fresh()->status)->toBe(CashRegisterStatus::Open);
});
