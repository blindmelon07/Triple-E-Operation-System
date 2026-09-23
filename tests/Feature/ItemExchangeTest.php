<?php

use App\Enums\CashRegisterStatus;
use App\Models\CashRegisterSession;
use App\Models\ExchangeItem;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\VoidRequest;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

// ─── Helpers (self-contained — avoids name collisions with other test files) ─

function iexRole(string $name): Role
{
    return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

function iexCashier(): User
{
    $user = User::factory()->create();
    $user->assignRole(iexRole('cashier'));
    return $user;
}

function iexAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(iexRole('admin'));
    return $user;
}

function iexSession(User $user, float $opening = 1000.0): CashRegisterSession
{
    return CashRegisterSession::create([
        'user_id'        => $user->id,
        'opening_amount' => $opening,
        'opened_at'      => now(),
        'status'         => CashRegisterStatus::Open,
    ]);
}

/**
 * A paid sale with two product line items (100 and 150 = 250 total), created
 * without the SaleItem observer so stock isn't double-decremented.
 */
function iexSaleWithTwoItems(CashRegisterSession $session): array
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

/** A product with a known stock level to exchange into. */
function iexReplacement(float $stock = 10): Product
{
    $product = Product::factory()->create();
    Inventory::where('product_id', $product->id)->first()->update(['quantity' => $stock]);
    return $product->fresh();
}

function iexExchangeRequest(
    Sale $sale,
    SaleItem $item,
    Product $replacement,
    User $cashier,
    CashRegisterSession $session,
    float $unitPrice,
    float $quantity = 1
): VoidRequest {
    return iexExchangeRequestMulti($sale, $item, [
        ['product' => $replacement, 'quantity' => $quantity, 'unit_price' => $unitPrice],
    ], $cashier, $session);
}

/**
 * Same as iexExchangeRequest() but takes several replacement lines — each a
 * ['product' => Product, 'quantity' => float, 'unit_price' => float, 'unit' => ?string].
 */
function iexExchangeRequestMulti(
    Sale $sale,
    SaleItem $item,
    array $lines,
    User $cashier,
    CashRegisterSession $session
): VoidRequest {
    $voidRequest = VoidRequest::create([
        'sale_id'                  => $sale->id,
        'sale_item_id'             => $item->id,
        'type'                     => 'exchange',
        'requested_by_id'          => $cashier->id,
        'cash_register_session_id' => $session->id,
        'void_reason'              => 'Customer wanted a different item',
        'status'                   => 'pending',
    ]);

    foreach ($lines as $line) {
        ExchangeItem::create([
            'void_request_id' => $voidRequest->id,
            'product_id'      => $line['product']->id,
            'quantity'        => $line['quantity'],
            'unit'            => $line['unit'] ?? $line['product']->unit->value,
            'unit_price'      => $line['unit_price'],
        ]);
    }

    return $voidRequest;
}

// ─── requestItemExchange (POST /pos/exchange-request-item/{saleItem}) ───────

describe('requestItemExchange', function () {

    it('cashier can submit an exchange request for a line item', function () {
        $cashier = iexCashier();
        iexAdmin();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement();

        actingAs($cashier);
        $response = postJson("/pos/exchange-request-item/{$itemA->id}", [
            'void_reason'   => 'Wrong size',
            'replacements'  => [
                ['product_id' => $replacement->id, 'quantity' => 1, 'unit' => $replacement->unit->value, 'unit_price' => 180],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $vr = VoidRequest::where('sale_item_id', $itemA->id)->where('status', 'pending')->first();
        expect($vr)->not->toBeNull();
        expect($vr->type)->toBe('exchange');
        expect($vr->isItemExchange())->toBeTrue();
        expect($vr->isItemVoid())->toBeFalse(); // an exchange is not an item void
        expect($vr->exchangeItems)->toHaveCount(1);
        expect((float) $vr->exchangeItems->first()->unit_price)->toBe(180.0);
    });

    it('cashier can submit an exchange request for multiple replacement products', function () {
        $cashier = iexCashier();
        iexAdmin();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacementA = iexReplacement();
        $replacementB = iexReplacement();

        actingAs($cashier);
        $response = postJson("/pos/exchange-request-item/{$itemA->id}", [
            'void_reason'  => 'Wanted two smaller items instead',
            'replacements' => [
                ['product_id' => $replacementA->id, 'quantity' => 1, 'unit' => $replacementA->unit->value, 'unit_price' => 60],
                ['product_id' => $replacementB->id, 'quantity' => 2, 'unit' => $replacementB->unit->value, 'unit_price' => 40],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $vr = VoidRequest::where('sale_item_id', $itemA->id)->where('status', 'pending')->first();
        expect($vr->exchangeItems)->toHaveCount(2);
        expect($vr->exchangeItems->pluck('product_id')->all())->toEqualCanonicalizing([$replacementA->id, $replacementB->id]);
    });

    it('rejects a request with no replacement lines', function () {
        $cashier = iexCashier();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);

        actingAs($cashier);
        $response = postJson("/pos/exchange-request-item/{$itemA->id}", [
            'void_reason'  => 'Swap',
            'replacements' => [],
        ]);

        $response->assertStatus(422);
        expect(VoidRequest::where('sale_item_id', $itemA->id)->exists())->toBeFalse();
    });

    it('refuses when two lines for the same product together exceed available stock', function () {
        $cashier = iexCashier();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement(stock: 5);

        actingAs($cashier);
        $response = postJson("/pos/exchange-request-item/{$itemA->id}", [
            'void_reason'  => 'Swap',
            'replacements' => [
                ['product_id' => $replacement->id, 'quantity' => 3, 'unit' => $replacement->unit->value, 'unit_price' => 50],
                ['product_id' => $replacement->id, 'quantity' => 3, 'unit' => $replacement->unit->value, 'unit_price' => 50],
            ],
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        expect(VoidRequest::where('sale_item_id', $itemA->id)->exists())->toBeFalse();
    });

    it('allows exchanging the only remaining item — unlike an item void', function () {
        $cashier = iexCashier();
        $session = iexSession($cashier);
        $sale = Sale::factory()->paid()->create([
            'cash_register_session_id' => $session->id,
            'total' => 100, 'amount_paid' => 100, 'payment_method' => 'cash', 'payment_term_days' => null,
        ]);
        $product = Product::factory()->create();
        $item = SaleItem::withoutEvents(fn () => SaleItem::create([
            'sale_id' => $sale->id, 'product_id' => $product->id, 'is_manual' => false,
            'unit' => 'piece', 'unit_price' => 100, 'quantity' => 1, 'price' => 100,
        ]));
        $replacement = iexReplacement();

        actingAs($cashier);
        $response = postJson("/pos/exchange-request-item/{$item->id}", [
            'void_reason'  => 'Swap',
            'replacements' => [
                ['product_id' => $replacement->id, 'quantity' => 1, 'unit' => $replacement->unit->value, 'unit_price' => 100],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
    });

    it('refuses when the replacement product has insufficient stock', function () {
        $cashier = iexCashier();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement(stock: 2);

        actingAs($cashier);
        $response = postJson("/pos/exchange-request-item/{$itemA->id}", [
            'void_reason'  => 'Swap',
            'replacements' => [
                ['product_id' => $replacement->id, 'quantity' => 5, 'unit' => $replacement->unit->value, 'unit_price' => 50],
            ],
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        expect(VoidRequest::where('sale_item_id', $itemA->id)->exists())->toBeFalse();
    });

    it('cannot exchange an item belonging to a different session', function () {
        $cashier1 = iexCashier();
        $cashier2 = iexCashier();
        $session1 = iexSession($cashier1);
        iexSession($cashier2);
        [$sale, $itemA] = iexSaleWithTwoItems($session1);
        $replacement = iexReplacement();

        actingAs($cashier2);
        $response = postJson("/pos/exchange-request-item/{$itemA->id}", [
            'void_reason'  => 'Wrong session',
            'replacements' => [
                ['product_id' => $replacement->id, 'quantity' => 1, 'unit' => $replacement->unit->value, 'unit_price' => 100],
            ],
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    });

    it('cannot request an exchange for an already-voided item', function () {
        $cashier = iexCashier();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $itemA->update(['is_voided' => true, 'voided_at' => now(), 'void_reason' => 'Already voided']);
        $replacement = iexReplacement();

        actingAs($cashier);
        $response = postJson("/pos/exchange-request-item/{$itemA->id}", [
            'void_reason'  => 'Swap',
            'replacements' => [
                ['product_id' => $replacement->id, 'quantity' => 1, 'unit' => $replacement->unit->value, 'unit_price' => 100],
            ],
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    });

});

// ─── approve — exchange (POST /pos/void-requests/{id}/approve) ──────────────

describe('approveItemExchangeRequest', function () {

    it('voids the outgoing item and writes the replacement as a new line item', function () {
        $cashier = iexCashier();
        $admin   = iexAdmin();
        $session = iexSession($cashier);
        [$sale, $itemA, $itemB] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement();

        $vr = iexExchangeRequest($sale, $itemA, $replacement, $cashier, $session, unitPrice: 180);

        actingAs($admin);
        $response = postJson("/pos/void-requests/{$vr->id}/approve");

        $response->assertOk()->assertJson(['success' => true]);

        expect($itemA->fresh()->is_voided)->toBeTrue();
        expect($itemA->fresh()->void_reason)->toContain('Exchanged:');
        expect($itemB->fresh()->is_voided)->toBeFalse();

        $newItem = SaleItem::where('sale_id', $sale->id)
            ->where('product_id', $replacement->id)
            ->first();

        expect($newItem)->not->toBeNull();
        expect((float) $newItem->price)->toBe(180.0);
        expect($newItem->is_voided)->toBeFalse();
        expect($vr->fresh()->status)->toBe('approved');
    });

    it('re-strikes the sale total around the price difference', function () {
        $cashier = iexCashier();
        $admin   = iexAdmin();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement();

        // 250 - 100 (out) + 180 (in) = 330
        $vr = iexExchangeRequest($sale, $itemA, $replacement, $cashier, $session, unitPrice: 180);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve");

        expect((float) $sale->fresh()->total)->toBe(330.0);
    });

    it('returns the outgoing stock and takes the replacement stock', function () {
        $cashier = iexCashier();
        $admin   = iexAdmin();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement(stock: 10);

        $vr = iexExchangeRequest($sale, $itemA, $replacement, $cashier, $session, unitPrice: 180, quantity: 3);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve");

        expect((float) $itemA->product->inventory->fresh()->quantity)->toBe(11.0); // 10 + 1 back
        expect((float) $replacement->inventory->fresh()->quantity)->toBe(7.0);     // 10 - 3 out
    });

    it('logs exactly one movement each way, both labelled as an exchange', function () {
        $cashier = iexCashier();
        $admin   = iexAdmin();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement();

        $vr = iexExchangeRequest($sale, $itemA, $replacement, $cashier, $session, unitPrice: 180);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve");

        $movements = InventoryMovement::where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->get();

        // SaleItemObserver already logs the outgoing row when the replacement line is
        // written — the controller must relabel it, not add a second one.
        $out = $movements->where('product_id', $replacement->id)->where('type', 'out');
        expect($out)->toHaveCount(1);
        expect($out->first()->reason)->toBe('Item Exchange');

        $in = $movements->where('product_id', $itemA->product_id)->where('type', 'in');
        expect($in)->toHaveCount(1);
        expect($in->first()->reason)->toBe('Item Exchange');
    });

    it('collects the extra into the register session when the replacement costs more', function () {
        $cashier = iexCashier();
        $admin   = iexAdmin();
        $session = iexSession($cashier);
        $session->update(['total_sales' => 250, 'total_cash_sales' => 250, 'total_transactions' => 1]);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement();

        // 100 out, 180 in → customer owes 80 more on an already-paid sale
        $vr = iexExchangeRequest($sale, $itemA, $replacement, $cashier, $session, unitPrice: 180);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve");

        $session->refresh();
        expect((float) $session->total_sales)->toBe(330.0);
        expect((float) $session->total_cash_sales)->toBe(330.0);
        expect($session->total_transactions)->toBe(1); // still one sale, not a new one

        $fresh = $sale->fresh();
        expect((float) $fresh->amount_paid)->toBe(330.0);
        expect($fresh->payment_status)->toBe('paid');
    });

    it('refunds out of the register session when the replacement costs less', function () {
        $cashier = iexCashier();
        $admin   = iexAdmin();
        $session = iexSession($cashier);
        $session->update(['total_sales' => 250, 'total_cash_sales' => 250, 'total_transactions' => 1]);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement();

        // 100 out, 60 in → 40 back to the customer
        $vr = iexExchangeRequest($sale, $itemA, $replacement, $cashier, $session, unitPrice: 60);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve");

        $session->refresh();
        expect((float) $session->total_sales)->toBe(210.0);
        expect((float) $session->total_cash_sales)->toBe(210.0);
        expect($session->total_transactions)->toBe(1);

        $fresh = $sale->fresh();
        expect((float) $fresh->total)->toBe(210.0);
        expect((float) $fresh->amount_paid)->toBe(210.0);
        expect($fresh->payment_status)->toBe('paid');
    });

    it('leaves the register session alone when the swap is price-neutral', function () {
        $cashier = iexCashier();
        $admin   = iexAdmin();
        $session = iexSession($cashier);
        $session->update(['total_sales' => 250, 'total_cash_sales' => 250, 'total_transactions' => 1]);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement();

        $vr = iexExchangeRequest($sale, $itemA, $replacement, $cashier, $session, unitPrice: 100);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve");

        $session->refresh();
        expect((float) $session->total_sales)->toBe(250.0);
        expect((float) $sale->fresh()->total)->toBe(250.0);
    });

    it('rejects the approval when the replacement no longer has stock', function () {
        $cashier = iexCashier();
        $admin   = iexAdmin();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement(stock: 10);

        $vr = iexExchangeRequest($sale, $itemA, $replacement, $cashier, $session, unitPrice: 50, quantity: 5);

        // Stock sells out between request and approval.
        $replacement->inventory->update(['quantity' => 1]);

        actingAs($admin);
        $response = postJson("/pos/void-requests/{$vr->id}/approve");

        $response->assertStatus(422)->assertJson(['success' => false]);

        // Nothing may be half-applied: the rollback must leave everything as it was.
        expect($itemA->fresh()->is_voided)->toBeFalse();
        expect((float) $sale->fresh()->total)->toBe(250.0);
        expect((float) $itemA->product->inventory->fresh()->quantity)->toBe(10.0);
        expect($vr->fresh()->status)->toBe('pending');
    });

    it('non-admin cannot approve an exchange request', function () {
        $cashier = iexCashier();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement();

        $vr = iexExchangeRequest($sale, $itemA, $replacement, $cashier, $session, unitPrice: 180);

        actingAs($cashier);
        $response = postJson("/pos/void-requests/{$vr->id}/approve");

        $response->assertStatus(403);
        expect($itemA->fresh()->is_voided)->toBeFalse();
    });

    it('swaps one outgoing item for two different replacement products', function () {
        $cashier = iexCashier();
        $admin   = iexAdmin();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacementA = iexReplacement();
        $replacementB = iexReplacement();

        // 100 out, (60 + 2*40=80) in = 140 → new total 250-100+140=290
        $vr = iexExchangeRequestMulti($sale, $itemA, [
            ['product' => $replacementA, 'quantity' => 1, 'unit_price' => 60],
            ['product' => $replacementB, 'quantity' => 2, 'unit_price' => 40],
        ], $cashier, $session);

        actingAs($admin);
        $response = postJson("/pos/void-requests/{$vr->id}/approve");

        $response->assertOk()->assertJson(['success' => true]);

        expect($itemA->fresh()->is_voided)->toBeTrue();

        $newItemA = SaleItem::where('sale_id', $sale->id)->where('product_id', $replacementA->id)->first();
        $newItemB = SaleItem::where('sale_id', $sale->id)->where('product_id', $replacementB->id)->first();
        expect((float) $newItemA->price)->toBe(60.0);
        expect((float) $newItemB->price)->toBe(80.0);

        expect((float) $sale->fresh()->total)->toBe(290.0);
        expect($vr->fresh()->status)->toBe('approved');
    });

    it('handles two replacement lines for the same product, decrementing stock by their sum', function () {
        $cashier = iexCashier();
        $admin   = iexAdmin();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement(stock: 10);

        $vr = iexExchangeRequestMulti($sale, $itemA, [
            ['product' => $replacement, 'quantity' => 2, 'unit_price' => 30],
            ['product' => $replacement, 'quantity' => 3, 'unit_price' => 30],
        ], $cashier, $session);

        actingAs($admin);
        $response = postJson("/pos/void-requests/{$vr->id}/approve");

        $response->assertOk()->assertJson(['success' => true]);

        expect((float) $replacement->inventory->fresh()->quantity)->toBe(5.0); // 10 - (2+3)

        $newItems = SaleItem::where('sale_id', $sale->id)->where('product_id', $replacement->id)->get();
        expect($newItems)->toHaveCount(2);

        // Each line must have its own outgoing movement, both relabelled — not one
        // shared row and not a mismatch between the two.
        $out = InventoryMovement::where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->where('product_id', $replacement->id)
            ->where('type', 'out')
            ->orderBy('id')
            ->get();
        expect($out)->toHaveCount(2);
        expect($out->pluck('reason')->unique()->all())->toBe(['Item Exchange']);
    });

    it('rolls back entirely when two lines for the same product together exceed stock', function () {
        $cashier = iexCashier();
        $admin   = iexAdmin();
        $session = iexSession($cashier);
        [$sale, $itemA] = iexSaleWithTwoItems($session);
        $replacement = iexReplacement(stock: 4);

        // Neither line alone exceeds stock, but 3 + 3 = 6 > 4 available.
        $vr = iexExchangeRequestMulti($sale, $itemA, [
            ['product' => $replacement, 'quantity' => 3, 'unit_price' => 30],
            ['product' => $replacement, 'quantity' => 3, 'unit_price' => 30],
        ], $cashier, $session);

        actingAs($admin);
        $response = postJson("/pos/void-requests/{$vr->id}/approve");

        $response->assertStatus(422)->assertJson(['success' => false]);

        expect($itemA->fresh()->is_voided)->toBeFalse();
        expect((float) $sale->fresh()->total)->toBe(250.0);
        expect((float) $replacement->inventory->fresh()->quantity)->toBe(4.0);
        expect(SaleItem::where('sale_id', $sale->id)->where('product_id', $replacement->id)->exists())->toBeFalse();
        expect($vr->fresh()->status)->toBe('pending');
    });

});
