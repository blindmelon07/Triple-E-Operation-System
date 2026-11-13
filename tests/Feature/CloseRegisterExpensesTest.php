<?php

use App\Enums\CashRegisterStatus;
use App\Models\CashRegisterSession;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

function expenseForSession(CashRegisterSession $session, array $overrides = []): Expense
{
    return Expense::create(array_merge([
        'expense_category_id' => ExpenseCategory::factory()->create()->id,
        'user_id' => $session->user_id,
        'reference_number' => 'EXP-TEST-' . uniqid(),
        'expense_date' => now()->toDateString(),
        'amount' => 200,
        'payment_method' => 'cash',
        'status' => 'pending',
    ], $overrides));
}

it('subtracts cash expenses paid during the shift from the expected closing cash', function () {
    $cashier = User::factory()->create();
    $session = CashRegisterSession::create([
        'user_id' => $cashier->id,
        'opening_amount' => 1000,
        'total_cash_sales' => 500,
        'opened_at' => now()->subHour(),
        'status' => CashRegisterStatus::Open,
    ]);

    expenseForSession($session, ['amount' => 200]);                                 // counted
    expenseForSession($session, ['amount' => 50, 'payment_method' => 'gcash']);       // not from the drawer
    expenseForSession($session, ['amount' => 70, 'status' => 'rejected']);            // rejected
    expenseForSession($session, ['amount' => 90, 'user_id' => User::factory()->create()->id]); // other cashier
    $old = expenseForSession($session, ['amount' => 30]);                             // before the shift
    $old->forceFill(['created_at' => now()->subDay()])->save();

    expect($session->totalCashExpenses())->toBe(200.0)
        ->and($session->calculateExpected())->toBe(1300.0);

    actingAs($cashier);
    postJson('/pos/register/close', ['closing_amount' => 1300])->assertOk();

    $session->refresh();
    expect((float) $session->expected_amount)->toBe(1300.0)
        ->and((float) $session->discrepancy)->toBe(0.0);
});
