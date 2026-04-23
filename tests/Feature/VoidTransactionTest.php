<?php

use App\Enums\CashRegisterStatus;
use App\Models\CashRegisterSession;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\VoidRequest;
use App\Notifications\VoidRequestNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeRole(string $name): Role
{
    return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

function makeCashier(): User
{
    $user = User::factory()->create();
    $user->assignRole(makeRole('cashier'));
    return $user;
}

function makeAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(makeRole('admin'));
    return $user;
}

function openSession(User $user, float $opening = 1000.0): CashRegisterSession
{
    return CashRegisterSession::create([
        'user_id'        => $user->id,
        'opening_amount' => $opening,
        'opened_at'      => now(),
        'status'         => CashRegisterStatus::Open,
    ]);
}

function makePaidSale(CashRegisterSession $session, float $total = 500.0, string $method = 'cash'): Sale
{
    return Sale::factory()->paid()->create([
        'cash_register_session_id' => $session->id,
        'total'                    => $total,
        'payment_method'           => $method,
        'payment_term_days'        => null,
    ]);
}

function attachProductItem(Sale $sale, int $qty = 2): array
{
    $product   = Product::factory()->create();
    $inventory = Inventory::where('product_id', $product->id)->first();

    // Set a known quantity so restoration is verifiable
    $inventory->update(['quantity' => 10]);

    // Create item without triggering the observer (which would decrement stock again)
    $item = SaleItem::withoutEvents(function () use ($sale, $product, $qty) {
        return SaleItem::create([
            'sale_id'    => $sale->id,
            'product_id' => $product->id,
            'is_manual'  => false,
            'unit'       => 'piece',
            'unit_price' => 50,
            'quantity'   => $qty,
            'price'      => 50 * $qty,
        ]);
    });

    return [$product, $inventory, $item];
}

// ─── requestVoid (POST /pos/void-request/{sale}) ────────────────────────────

describe('requestVoid', function () {

    it('cashier can submit a void request for a sale in their session', function () {
        Notification::fake();

        $cashier = makeCashier();
        $admin   = makeAdmin();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);

        actingAs($cashier);

        $response = postJson("/pos/void-request/{$sale->id}", ['void_reason' => 'Wrong item']);

        $response->assertOk()->assertJson(['success' => true]);
        expect(VoidRequest::where('sale_id', $sale->id)->where('status', 'pending')->exists())->toBeTrue();
        Notification::assertSentTo($admin, VoidRequestNotification::class);
    });

    it('returns same void_request_id if a pending request already exists', function () {
        $cashier = makeCashier();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);
        makeAdmin();

        actingAs($cashier);
        postJson("/pos/void-request/{$sale->id}", ['void_reason' => 'Duplicate test']);
        $first = VoidRequest::where('sale_id', $sale->id)->first();

        $response = postJson("/pos/void-request/{$sale->id}", ['void_reason' => 'Duplicate test']);
        $response->assertOk()->assertJson(['void_request_id' => $first->id]);
    });

    it('cannot void a sale that belongs to a different session', function () {
        $cashier  = makeCashier();
        $cashier2 = makeCashier();
        $session  = openSession($cashier);
        $session2 = openSession($cashier2);
        $sale     = makePaidSale($session2); // belongs to cashier2's session

        actingAs($cashier);
        $response = postJson("/pos/void-request/{$sale->id}", ['void_reason' => 'Wrong session']);

        $response->assertStatus(422)->assertJson(['success' => false]);
    });

    it('cannot void a sale that is already voided', function () {
        $cashier = makeCashier();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);
        $sale->update(['is_voided' => true, 'voided_at' => now(), 'void_reason' => 'Already done']);

        actingAs($cashier);
        $response = postJson("/pos/void-request/{$sale->id}", ['void_reason' => 'Re-void attempt']);

        $response->assertStatus(422)->assertJson(['success' => false]);
    });

    it('cannot void without an open register session', function () {
        $cashier = makeCashier();
        $sale    = Sale::factory()->paid()->create();

        actingAs($cashier);
        $response = postJson("/pos/void-request/{$sale->id}", ['void_reason' => 'No session']);

        $response->assertStatus(422)->assertJson(['success' => false]);
    });

    it('requires a void reason', function () {
        $cashier = makeCashier();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);

        actingAs($cashier);
        $response = postJson("/pos/void-request/{$sale->id}", ['void_reason' => '']);

        $response->assertStatus(422);
    });

});

// ─── approve (POST /pos/void-requests/{id}/approve) ─────────────────────────

describe('approveVoidRequest', function () {

    it('admin can approve a void request and sale becomes voided', function () {
        $cashier = makeCashier();
        $admin   = makeAdmin();
        $session = openSession($cashier);
        $sale    = makePaidSale($session, 500, 'cash');

        $vr = VoidRequest::create([
            'sale_id'                  => $sale->id,
            'requested_by_id'          => $cashier->id,
            'cash_register_session_id' => $session->id,
            'void_reason'              => 'Customer returned',
            'status'                   => 'pending',
        ]);

        actingAs($admin);
        $response = postJson("/pos/void-requests/{$vr->id}/approve");

        $response->assertOk()->assertJson(['success' => true]);
        expect($sale->fresh()->is_voided)->toBeTrue();
        expect($vr->fresh()->status)->toBe('approved');
        expect($vr->fresh()->reviewed_by_id)->toBe($admin->id);
    });

    it('approval restores inventory for non-manual items', function () {
        $cashier = makeCashier();
        $admin   = makeAdmin();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);

        [$product, $inventory] = attachProductItem($sale, 2);
        // inventory is 10, sale used 2 (not decremented in test setup), approval should add 2 back
        $inventoryBefore = (float) $inventory->fresh()->quantity;

        $vr = VoidRequest::create([
            'sale_id'                  => $sale->id,
            'requested_by_id'          => $cashier->id,
            'cash_register_session_id' => $session->id,
            'void_reason'              => 'Return',
            'status'                   => 'pending',
        ]);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve");

        expect((float) $inventory->fresh()->quantity)->toBe($inventoryBefore + 2);
    });

    it('approval reverses cash register totals for paid cash sales', function () {
        $cashier = makeCashier();
        $admin   = makeAdmin();
        $session = openSession($cashier);
        $session->update([
            'total_sales'       => 500,
            'total_cash_sales'  => 500,
            'total_transactions' => 1,
        ]);

        $sale = makePaidSale($session, 500, 'cash');

        $vr = VoidRequest::create([
            'sale_id'                  => $sale->id,
            'requested_by_id'          => $cashier->id,
            'cash_register_session_id' => $session->id,
            'void_reason'              => 'Void',
            'status'                   => 'pending',
        ]);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve");

        $session->refresh();
        expect((float) $session->total_sales)->toBe(0.0);
        expect((float) $session->total_cash_sales)->toBe(0.0);
        expect($session->total_transactions)->toBe(0);
    });

    it('approval does NOT reverse register totals for credit sales', function () {
        $cashier = makeCashier();
        $admin   = makeAdmin();
        $session = openSession($cashier);
        $session->update(['total_sales' => 0, 'total_cash_sales' => 0, 'total_transactions' => 0]);

        $sale = Sale::factory()->create([
            'cash_register_session_id' => $session->id,
            'total'                    => 500,
            'payment_method'           => 'charge',
            'payment_status'           => 'unpaid',
            'payment_term_days'        => 30,
            'amount_paid'              => 0,
        ]);

        $vr = VoidRequest::create([
            'sale_id'                  => $sale->id,
            'requested_by_id'          => $cashier->id,
            'cash_register_session_id' => $session->id,
            'void_reason'              => 'Void credit',
            'status'                   => 'pending',
        ]);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve");

        $session->refresh();
        expect((float) $session->total_sales)->toBe(0.0);
    });

    it('non-admin cannot approve a void request', function () {
        $cashier = makeCashier();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);

        $vr = VoidRequest::create([
            'sale_id'         => $sale->id,
            'requested_by_id' => $cashier->id,
            'void_reason'     => 'Test',
            'status'          => 'pending',
        ]);

        actingAs($cashier);
        $response = postJson("/pos/void-requests/{$vr->id}/approve");

        $response->assertStatus(403);
        expect($sale->fresh()->is_voided)->toBeFalse();
    });

    it('cannot approve an already reviewed request', function () {
        $cashier = makeCashier();
        $admin   = makeAdmin();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);

        $vr = VoidRequest::create([
            'sale_id'         => $sale->id,
            'requested_by_id' => $cashier->id,
            'void_reason'     => 'Test',
            'status'          => 'rejected',
        ]);

        actingAs($admin);
        $response = postJson("/pos/void-requests/{$vr->id}/approve");

        $response->assertStatus(422);
    });

});

// ─── reject (POST /pos/void-requests/{id}/reject) ───────────────────────────

describe('rejectVoidRequest', function () {

    it('admin can reject a void request with a reason', function () {
        $cashier = makeCashier();
        $admin   = makeAdmin();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);

        $vr = VoidRequest::create([
            'sale_id'         => $sale->id,
            'requested_by_id' => $cashier->id,
            'void_reason'     => 'Test',
            'status'          => 'pending',
        ]);

        actingAs($admin);
        $response = postJson("/pos/void-requests/{$vr->id}/reject", [
            'rejection_reason' => 'Sale is valid',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        expect($vr->fresh()->status)->toBe('rejected');
        expect($vr->fresh()->rejection_reason)->toBe('Sale is valid');
        expect($sale->fresh()->is_voided)->toBeFalse();
    });

    it('requires a rejection reason', function () {
        $cashier = makeCashier();
        $admin   = makeAdmin();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);

        $vr = VoidRequest::create([
            'sale_id'         => $sale->id,
            'requested_by_id' => $cashier->id,
            'void_reason'     => 'Test',
            'status'          => 'pending',
        ]);

        actingAs($admin);
        $response = postJson("/pos/void-requests/{$vr->id}/reject", ['rejection_reason' => '']);

        $response->assertStatus(422);
    });

    it('non-admin cannot reject', function () {
        $cashier = makeCashier();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);

        $vr = VoidRequest::create([
            'sale_id'         => $sale->id,
            'requested_by_id' => $cashier->id,
            'void_reason'     => 'Test',
            'status'          => 'pending',
        ]);

        actingAs($cashier);
        $response = postJson("/pos/void-requests/{$vr->id}/reject", ['rejection_reason' => 'No']);

        $response->assertStatus(403);
    });

});

// ─── cancel (POST /pos/void-requests/{id}/cancel) ───────────────────────────

describe('cancelVoidRequest', function () {

    it('cashier can cancel their own pending void request', function () {
        $cashier = makeCashier();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);

        $vr = VoidRequest::create([
            'sale_id'         => $sale->id,
            'requested_by_id' => $cashier->id,
            'void_reason'     => 'Test',
            'status'          => 'pending',
        ]);

        actingAs($cashier);
        $response = postJson("/pos/void-requests/{$vr->id}/cancel");

        $response->assertOk()->assertJson(['success' => true]);
        expect($vr->fresh()->status)->toBe('rejected');
    });

    it('cashier cannot cancel another cashier\'s void request', function () {
        $cashier1 = makeCashier();
        $cashier2 = makeCashier();
        $session  = openSession($cashier1);
        $sale     = makePaidSale($session);

        $vr = VoidRequest::create([
            'sale_id'         => $sale->id,
            'requested_by_id' => $cashier1->id,
            'void_reason'     => 'Test',
            'status'          => 'pending',
        ]);

        actingAs($cashier2);
        $response = postJson("/pos/void-requests/{$vr->id}/cancel");

        $response->assertStatus(403);
    });

});

// ─── status (GET /pos/void-requests/{id}/status) ────────────────────────────

describe('voidRequestStatus', function () {

    it('cashier can poll status of their own void request', function () {
        $cashier = makeCashier();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);

        $vr = VoidRequest::create([
            'sale_id'         => $sale->id,
            'requested_by_id' => $cashier->id,
            'void_reason'     => 'Test',
            'status'          => 'pending',
        ]);

        actingAs($cashier);
        $response = getJson("/pos/void-requests/{$vr->id}/status");

        $response->assertOk()->assertJson(['success' => true, 'status' => 'pending']);
    });

    it('returns approved status after approval', function () {
        $cashier = makeCashier();
        $admin   = makeAdmin();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);

        $vr = VoidRequest::create([
            'sale_id'                  => $sale->id,
            'requested_by_id'          => $cashier->id,
            'cash_register_session_id' => $session->id,
            'void_reason'              => 'Test',
            'status'                   => 'pending',
        ]);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/approve");

        actingAs($cashier);
        $response = getJson("/pos/void-requests/{$vr->id}/status");
        $response->assertJson(['status' => 'approved']);
    });

    it('returns rejected status with reason', function () {
        $cashier = makeCashier();
        $admin   = makeAdmin();
        $session = openSession($cashier);
        $sale    = makePaidSale($session);

        $vr = VoidRequest::create([
            'sale_id'         => $sale->id,
            'requested_by_id' => $cashier->id,
            'void_reason'     => 'Test',
            'status'          => 'pending',
        ]);

        actingAs($admin);
        postJson("/pos/void-requests/{$vr->id}/reject", ['rejection_reason' => 'Not authorized']);

        actingAs($cashier);
        $response = getJson("/pos/void-requests/{$vr->id}/status");
        $response->assertJson(['status' => 'rejected', 'rejection_reason' => 'Not authorized']);
    });

    it('another cashier cannot poll someone else\'s void request', function () {
        $cashier1 = makeCashier();
        $cashier2 = makeCashier();
        $session  = openSession($cashier1);
        $sale     = makePaidSale($session);

        $vr = VoidRequest::create([
            'sale_id'         => $sale->id,
            'requested_by_id' => $cashier1->id,
            'void_reason'     => 'Test',
            'status'          => 'pending',
        ]);

        actingAs($cashier2);
        $response = getJson("/pos/void-requests/{$vr->id}/status");
        $response->assertStatus(403);
    });

});

// ─── pending (GET /pos/void-requests/pending) ───────────────────────────────

describe('pendingVoidRequests', function () {

    it('admin can see all pending void requests', function () {
        $cashier = makeCashier();
        $admin   = makeAdmin();
        $session = openSession($cashier);

        Sale::factory()->paid()->count(3)->create(['cash_register_session_id' => $session->id])
            ->each(fn ($sale) => VoidRequest::create([
                'sale_id'         => $sale->id,
                'requested_by_id' => $cashier->id,
                'void_reason'     => 'Test',
                'status'          => 'pending',
            ]));

        actingAs($admin);
        $response = getJson('/pos/void-requests/pending');

        $response->assertOk()->assertJson(['success' => true]);
        expect(count($response->json('requests')))->toBe(3);
    });

    it('non-admin cannot access pending void requests', function () {
        $cashier = makeCashier();

        actingAs($cashier);
        $response = getJson('/pos/void-requests/pending');

        $response->assertStatus(403);
    });

    it('only returns pending (not approved or rejected) requests', function () {
        $cashier = makeCashier();
        $admin   = makeAdmin();
        $session = openSession($cashier);

        $sales = Sale::factory()->paid()->count(3)->create(['cash_register_session_id' => $session->id]);

        VoidRequest::create(['sale_id' => $sales[0]->id, 'requested_by_id' => $cashier->id, 'void_reason' => 'A', 'status' => 'pending']);
        VoidRequest::create(['sale_id' => $sales[1]->id, 'requested_by_id' => $cashier->id, 'void_reason' => 'B', 'status' => 'approved']);
        VoidRequest::create(['sale_id' => $sales[2]->id, 'requested_by_id' => $cashier->id, 'void_reason' => 'C', 'status' => 'rejected']);

        actingAs($admin);
        $response = getJson('/pos/void-requests/pending');

        expect(count($response->json('requests')))->toBe(1);
    });

});
