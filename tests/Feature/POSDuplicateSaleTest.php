<?php

use App\Models\Sale;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::factory()->create());

    $this->payload = [
        'customer_id' => null,
        'items' => [[
            'is_manual' => true,
            'name' => 'Manual item',
            'quantity' => 1,
            'price' => 150,
            'unit_price' => 150,
            'unit' => 'pc',
        ]],
        'total' => 150,
        'payment_method' => 'cash',
        'cash_received' => 150,
        'change' => 0,
    ];
});

it('records a sale only once when the same checkout is submitted twice', function () {
    $payload = $this->payload + ['sale_token' => 'checkout-abc'];

    $first = postJson('/pos/complete-sale', $payload)->assertOk()->json();
    $second = postJson('/pos/complete-sale', $payload)->assertOk()->json();

    expect($second['sale_id'])->toBe($first['sale_id'])
        ->and(Sale::count())->toBe(1);
});

it('records separate sales for separate checkouts', function () {
    postJson('/pos/complete-sale', $this->payload + ['sale_token' => 'checkout-1'])->assertOk();
    postJson('/pos/complete-sale', $this->payload + ['sale_token' => 'checkout-2'])->assertOk();

    expect(Sale::count())->toBe(2);
});
