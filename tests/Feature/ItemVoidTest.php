<?php

use App\Enums\CashRegisterStatus;
use App\Models\CashRegisterSession;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\VoidRequest;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

// ─── Helpers (self-contained — avoids name collisions with other test files) ─

function ivRole(string $name): Role
{
    return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

function ivCashier(): User
{
    $user = User::factory()->create();
    $user->assignRole(ivRole('cashier'));
    return $user;
}

function ivAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(ivRole('admin'));
    return $user;
}

function ivSession(User $user, float $opening = 1000.0): CashRegisterSession
{
    return CashRegisterSession::create([
        'user_id'        => $user->id,
        'opening_amount' => $opening,
        'opened_at'      => now(),
        'status'         => CashRegisterStatus::Open,
    ]);
}

/**
 * A paid sale with two product line items (100 and 150 = 250 total), each
 * with its own tracked inventory, without the SaleItem observer double-
 * decrementing stock (mirrors VoidTransactionTest's attachProductItem).
 */
function ivSaleWithTwoItems(CashRegisterSession $session): array
{
    $sale = Sale::factory()->paid()->create([
        'cash_register_session_id' => $session->id,
        'total'                    => 250,
        'amount_paid'              => 250,
        'payment_method'           => 'cash',
        'payment_term_days'        => null,
    ]);

    $productA = Product::factory()->create();
    Inventory::where('product_id', $productA->id)->first()->update(['quantity' => 10]);
    $itemA = SaleItem::withoutEvents(fn () => SaleItem::create([
        'sale_id' => $sale->id, 'product_id' => $productA->id, 'is_manual' => false,
        'unit' => 'piece', 'unit_price' => 100, 'quantity' => 1, 'price' => 100,
    ]));

    $productB = Product::factory()->create();
    Inventory::where('product_id', $productB->id)->first()->update(['quantity' => 5]);
    $itemB = SaleItem::withoutEvents(fn () => SaleItem::create([
        'sale_id' => $sale->id, 'product_id' => $productB->id, 'is_manual' => false,
        'unit' => 'piece', 'unit_price' => 150, 'quantity' => 1, 'price' => 150,
    ]));

    return [$sale, $itemA, $itemB];
}

// ─── requestItemVoid (POST /pos/void-request-item/{saleItem}) ───────────────

describe('requestItemVoid', function () {

    it('cashier can submit a void request for one item in a multi-item sale', function () {
        $cashier = ivCashier();
        ivAdmin();
        $session = ivSession($cashier);
        [$sale, $itemA] = ivSaleWithTwoItems($session);

        actingAs($cashier);
        $response = postJson("/pos/void-request-item/{$itemA->id}", ['void_reason' => 'Wrong item']);

        $response->assertOk()->assertJson(['success' => true]);
        expect(VoidRequest::where('sale_item_id', $itemA->id)->where('status', 'pending')->exists())->toBeTrue();
    });

    it('refuses to void the only remaining item — must void the whole sale instead', function () {
        $cashier = ivCashier();
        $session = ivSession($cashier);
        $sale = Sale::factory()->paid()->create([
            'cash_register_session_id' => $session->id,
            'total' => 100, 'amount_paid' => 100, 'payment_method' => 'cash', 'payment_term_days' => null,
        ]);
        $product = Product::factory()->create();
        $item = SaleItem::withoutEvents(fn () => SaleItem::create([
            'sale_id' => $sale->id, 'product_id' => $product->id, 'is_manual' => false,
            'unit' => 'piece', 'unit_price' => 100, 'quantity' => 1, 'price' => 100,
        ]));

        actingAs($cashier);
        $response = postJson("/pos/void-request-item/{$item->id}", ['void_reason' => 'Test']);

        $response->assertStatus(422)->assertJson(['success' => false]);
    });

    it('cannot request void for an already-voided item', function () {
        $cashier = ivCashier();
        $session = ivSession($cashier);
        [$sale, $itemA] = ivSaleWithTwoItems($session);
        $itemA->update(['is_voided' => true, 'voided_at' => now(), 'void_reason' => 'Already voided']);

        actingAs($cashier);
        $response = postJson("/pos/void-request-item/{$itemA->id}", ['void_reason' => 'Again']);

        $response->assertStatus(422)->assertJson(['success' => false]);
    });

    it('cannot void an item belonging to a different session', function () {
        $cashier1 = ivCashier();
        $cashier2 = ivCashier();
        $session1 = ivSession($cashier1);
        ivSession($cashier2);
        [$sale, $itemA] = ivSaleWithTwoItems($session1);

        actingAs($cashier2);
        $response = postJson("/pos/void-request-item/{$itemA->id}", ['void_reason' => 'Wrong session']);

        $response->assertStatus(422)->assertJson(['success' => false]);
    });

});

// ─── approve — item-level (POST /pos/void-requests/{id}/approve) ────────────

describe('approveItemVoidRequest', function () {

    it('reduces the sale total by the item price and leaves the other item intact', function () {
        $cashier = ivCashier();
        $admin   = ivAdmin();
        $session = ivSession($cashier);
        [$sale, $itemA, $itemB] = ivSaleWithTwoItems($session);

        $vr = VoidRequest::create([
            'sale_id' => $sale->id, 'sale_item_id' => $itemA->id,
            'requested_by_id' => $cashier->id, 'cash_register_session_id' => $session->id,
            'void_reason' => 'Wrong item', 'status' => 'pending',
        ]);

        actingAs($admin);
        $response = postJson("/pos/void-requests/{$vr->id}/approve");

        $response->assertOk()->assertJson(['success' => true]);
        expect((float) $sale->fresh()->total)->toBe(150.0); // 250 - 100
        expect($itemA->fresh()->is_voided)->toBeTrue();
        expect($itemB->fresh()->is_voided)->toBeFalse();
        expect($vr->fresh()->status)->toBe('approved');
    });

    it('restores inventory for only the voided item', function () {
        $cashier = ivCashier();
        $admin   = ivAdmin();
        $session = ivSession($cashier);
        [$sale, $itemA, $itemB] = ivSaleWithTwoItems($session);

        $inventoryABefore = (float) $itemA->product->inventory->fresh()->quantity;
        $inventoryBBefore = (float) $itemB->product->inventory->fresh()->quantity;

        $vr = VoidRequest::create([
            'sale_id' => $sale->id, 'sale_item_id' => $itemA->id,
            'requested_by_id' => $cashier->id, 'cash_register_session_id' => $session->id,
            'void_reason' => 'Wrong item', 'status' => 'pending',
        ]);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve");

        expect((float) $itemA->product->inventory->fresh()->quantity)->toBe($inventoryABefore + 1);
        expect((float) $itemB->product->inventory->fresh()->quantity)->toBe($inventoryBBefore); // untouched
    });

    it('refunds the item amount from the register session without touching transaction count', function () {
        $cashier = ivCashier();
        $admin   = ivAdmin();
        $session = ivSession($cashier);
        $session->update(['total_sales' => 250, 'total_cash_sales' => 250, 'total_transactions' => 1]);
        [$sale, $itemA] = ivSaleWithTwoItems($session);

        $vr = VoidRequest::create([
            'sale_id' => $sale->id, 'sale_item_id' => $itemA->id,
            'requested_by_id' => $cashier->id, 'cash_register_session_id' => $session->id,
            'void_reason' => 'Wrong item', 'status' => 'pending',
        ]);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve");

        $session->refresh();
        expect((float) $session->total_sales)->toBe(150.0);      // 250 - 100
        expect((float) $session->total_cash_sales)->toBe(150.0); // was cash
        expect($session->total_transactions)->toBe(1);           // sale still stands — unchanged
    });

    it('keeps payment_status as paid when the remaining amount still covers the new total', function () {
        $cashier = ivCashier();
        $admin   = ivAdmin();
        $session = ivSession($cashier);
        [$sale, $itemA] = ivSaleWithTwoItems($session);

        $vr = VoidRequest::create([
            'sale_id' => $sale->id, 'sale_item_id' => $itemA->id,
            'requested_by_id' => $cashier->id, 'cash_register_session_id' => $session->id,
            'void_reason' => 'Wrong item', 'status' => 'pending',
        ]);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve");

        $fresh = $sale->fresh();
        expect($fresh->payment_status)->toBe('paid');
        expect((float) $fresh->amount_paid)->toBe(150.0);
    });

    it('non-admin cannot approve an item void request', function () {
        $cashier = ivCashier();
        $session = ivSession($cashier);
        [$sale, $itemA] = ivSaleWithTwoItems($session);

        $vr = VoidRequest::create([
            'sale_id' => $sale->id, 'sale_item_id' => $itemA->id,
            'requested_by_id' => $cashier->id, 'void_reason' => 'Test', 'status' => 'pending',
        ]);

        actingAs($cashier);
        $response = postJson("/pos/void-requests/{$vr->id}/approve");

        $response->assertStatus(403);
        expect($itemA->fresh()->is_voided)->toBeFalse();
    });

});
