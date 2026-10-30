<?php

use App\Enums\CashRegisterStatus;
use App\Models\CashRegisterSession;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $user = User::factory()->create();
    actingAs($user);

    $this->session = CashRegisterSession::create([
        'user_id' => $user->id,
        'opening_amount' => 1000,
        'opened_at' => now(),
        'status' => CashRegisterStatus::Open,
    ]);

    $this->product = Product::factory()->create();
    Inventory::where('product_id', $this->product->id)->first()->update(['quantity' => 10]);

    $this->payload = [
        'customer_id' => null,
        'cash_register_session_id' => $this->session->id,
        'items' => [[
            'id' => $this->product->id,
            'name' => $this->product->name,
            'quantity' => 2,
            'price' => 200,
            'unit_price' => 100,
            'unit' => 'piece',
        ]],
        'total' => 200,
        'payment_method' => 'cash',
    ];
});

it('voids the second copy of a double-submitted sale and restores stock and register totals', function () {
    // Same checkout sent twice without a token — how the old POS double-recorded sales.
    $first = postJson('/pos/complete-sale', $this->payload)->json('sale_id');
    $second = postJson('/pos/complete-sale', $this->payload)->json('sale_id');

    expect(Inventory::where('product_id', $this->product->id)->value('quantity'))->toEqual(6);

    artisan('sales:remove-duplicates', ['--apply' => true])->assertSuccessful();

    expect(Sale::find($first)->is_voided)->toBeFalse()
        ->and(Sale::find($second)->is_voided)->toBeTrue()
        ->and(Inventory::where('product_id', $this->product->id)->value('quantity'))->toEqual(8);

    $session = $this->session->fresh();
    expect((float) $session->total_sales)->toBe(200.0)
        ->and((float) $session->total_cash_sales)->toBe(200.0)
        ->and($session->total_transactions)->toBe(1);
});

it('only previews without --apply', function () {
    postJson('/pos/complete-sale', $this->payload);
    $second = postJson('/pos/complete-sale', $this->payload)->json('sale_id');

    artisan('sales:remove-duplicates')->assertSuccessful();

    expect(Sale::find($second)->is_voided)->toBeFalse();
});

it('leaves identical sales alone when they are further apart than the window', function () {
    postJson('/pos/complete-sale', $this->payload);
    $this->travel(5)->minutes();
    $second = postJson('/pos/complete-sale', $this->payload)->json('sale_id');

    artisan('sales:remove-duplicates', ['--apply' => true])->assertSuccessful();

    expect(Sale::find($second)->is_voided)->toBeFalse();
});
