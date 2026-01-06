<?php

use App\Models\Customer;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Customer CRUD Operations', function () {
    it('can create a customer', function () {
        actingAs($this->user);

        $customerData = [
            'name' => 'Test Customer',
            'phone' => '09171234567',
            'email' => 'customer@test.com',
            'address' => '123 Test Street',
        ];

        $customer = Customer::create($customerData);

        expect($customer)->toBeInstanceOf(Customer::class)
            ->and($customer->name)->toBe('Test Customer')
            ->and($customer->email)->toBe('customer@test.com');

        $this->assertDatabaseHas('customers', [
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
        ]);
    });

    it('can read a customer', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create([
            'name' => 'Read Test Customer',
        ]);

        $foundCustomer = Customer::find($customer->id);

        expect($foundCustomer)->not->toBeNull()
            ->and($foundCustomer->name)->toBe('Read Test Customer');
    });

    it('can read all customers', function () {
        actingAs($this->user);

        Customer::factory()->count(5)->create();

        $customers = Customer::all();

        expect($customers)->toHaveCount(5);
    });

    it('can update a customer', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create([
            'name' => 'Original Customer',
        ]);

        $customer->update([
            'name' => 'Updated Customer',
        ]);

        $fresh = $customer->fresh();
        expect($fresh->name)->toBe('Updated Customer');
    });

    it('can delete a customer', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();
        $customerId = $customer->id;

        $customer->delete();

        $this->assertDatabaseMissing('customers', ['id' => $customerId]);
    });

    it('can have sales relationship', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();

        expect($customer->sales())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });
});
