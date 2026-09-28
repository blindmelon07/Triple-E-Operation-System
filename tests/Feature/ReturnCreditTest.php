<?php

use App\Enums\CashRegisterStatus;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\User;
use App\Models\VoidRequest;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

// ─── Helpers (self-contained — avoids name collisions with other test files) ─

function rcUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
    return $user;
}

function rcSession(User $user): CashRegisterSession
{
    return CashRegisterSession::create([
        'user_id'            => $user->id,
        'opening_amount'     => 1000,
        'opened_at'          => now(),
        'status'             => CashRegisterStatus::Open,
        'total_sales'        => 250,
        'total_cash_sales'   => 250,
        'total_transactions' => 1,
    ]);
}

/** Last month's paid sale (100 + 150) for $customer, plus a pending void of the 100 item. */
function rcPaidSaleWithVoid(Customer $customer, CashRegisterSession $session, User $cashier): array
{
    $sale = Sale::factory()->paid()->create([
        'customer_id'              => $customer->id,
        'cash_register_session_id' => $session->id,
        'date'                     => now()->subMonth(),
        'total'                    => 250,
        'amount_paid'              => 250,
        'payment_method'           => 'cash',
        'payment_term_days'        => null,
    ]);

    $items = [];
    foreach ([100, 150] as $price) {
        $product = Product::factory()->create();
        Inventory::where('product_id', $product->id)->first()->update(['quantity' => 10]);
        $items[] = SaleItem::withoutEvents(fn () => SaleItem::create([
            'sale_id' => $sale->id, 'product_id' => $product->id, 'is_manual' => false,
            'unit' => 'piece', 'unit_price' => $price, 'quantity' => 1, 'price' => $price,
        ]));
    }

    $vr = VoidRequest::create([
        'sale_id'                  => $sale->id,
        'sale_item_id'             => $items[0]->id,
        'requested_by_id'          => $cashier->id,
        'cash_register_session_id' => $session->id,
        'void_reason'              => 'Returned item',
        'status'                   => 'pending',
    ]);

    return [$sale, $vr];
}

function rcUnpaidSale(Customer $customer, float $total, string $dueDate): Sale
{
    return Sale::factory()->create([
        'customer_id'       => $customer->id,
        'total'             => $total,
        'amount_paid'       => 0,
        'payment_status'    => 'unpaid',
        'payment_method'    => 'charge',
        'is_voided'         => false,
        'due_date'          => $dueDate,
        'payment_term_days' => 30,
    ]);
}

// ─── Deducting a returned item from the customer's balance ──────────────────

describe('return credit on item void', function () {

    it('deducts the returned item from the customer\'s unpaid invoices instead of refunding cash', function () {
        $cashier  = rcUser('cashier');
        $admin    = rcUser('admin');
        $session  = rcSession($cashier);
        $customer = Customer::factory()->create();
        [$sale, $vr] = rcPaidSaleWithVoid($customer, $session, $cashier);

        $older = rcUnpaidSale($customer, 60, now()->subDays(5)->toDateString());
        $newer = rcUnpaidSale($customer, 500, now()->addDays(10)->toDateString());

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve", ['refund_mode' => 'credit'])
            ->assertOk()->assertJson(['success' => true]);

        // Oldest due invoice is cleared first, the rest goes to the next one.
        expect($older->fresh()->payment_status)->toBe('paid')
            ->and((float) $older->fresh()->amount_paid)->toBe(60.0)
            ->and($newer->fresh()->payment_status)->toBe('partial')
            ->and((float) $newer->fresh()->amount_paid)->toBe(40.0);

        // Source sale shrinks as usual.
        expect((float) $sale->fresh()->total)->toBe(150.0)
            ->and((float) $sale->fresh()->amount_paid)->toBe(150.0);

        // No cash left the drawer.
        $session->refresh();
        expect((float) $session->total_sales)->toBe(250.0)
            ->and((float) $session->total_cash_sales)->toBe(250.0);

        // Credits are logged with no register session so they stay out of drawer reports.
        $credits = SalePayment::where('payment_method', 'return_credit')->get();
        expect($credits)->toHaveCount(2)
            ->and($credits->whereNotNull('cash_register_session_id'))->toHaveCount(0)
            ->and((float) $credits->sum('amount'))->toBe(100.0);

        $vr->refresh();
        expect($vr->refund_mode)->toBe('credit')
            ->and((float) $vr->credited_amount)->toBe(100.0);
    });

    it('refunds in cash only what the customer\'s open balance cannot absorb', function () {
        $cashier  = rcUser('cashier');
        $admin    = rcUser('admin');
        $session  = rcSession($cashier);
        $customer = Customer::factory()->create();
        [, $vr] = rcPaidSaleWithVoid($customer, $session, $cashier);

        $open = rcUnpaidSale($customer, 30, now()->toDateString());

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve", ['refund_mode' => 'credit'])->assertOk();

        expect($open->fresh()->payment_status)->toBe('paid');
        expect((float) $session->fresh()->total_cash_sales)->toBe(180.0); // 250 - 70 cash back
        expect((float) $vr->fresh()->credited_amount)->toBe(30.0);
    });

    it('still refunds cash when no refund mode is given', function () {
        $cashier  = rcUser('cashier');
        $admin    = rcUser('admin');
        $session  = rcSession($cashier);
        $customer = Customer::factory()->create();
        [, $vr] = rcPaidSaleWithVoid($customer, $session, $cashier);

        $open = rcUnpaidSale($customer, 500, now()->toDateString());

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve")->assertOk();

        expect((float) $open->fresh()->amount_paid)->toBe(0.0);
        expect((float) $session->fresh()->total_cash_sales)->toBe(150.0);
        expect($vr->fresh()->refund_mode)->toBe('cash');
    });

    it('shows the approver the refund and the customer\'s other open balance', function () {
        $cashier  = rcUser('cashier');
        $admin    = rcUser('admin');
        $session  = rcSession($cashier);
        $customer = Customer::factory()->create();
        [$sale] = rcPaidSaleWithVoid($customer, $session, $cashier);
        rcUnpaidSale($customer, 500, now()->toDateString());

        actingAs($admin);
        $row = collect(getJson('/pos/void-requests/pending')->json('requests'))->firstWhere('sale_id', $sale->id);

        expect((float) $row['refund_amount'])->toBe(100.0)
            ->and((float) $row['customer_open_balance'])->toBe(500.0);
    });
});
