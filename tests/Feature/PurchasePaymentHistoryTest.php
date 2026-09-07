<?php

use App\Filament\Resources\Purchases\Pages\EditPurchase;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();

    foreach (['ViewAny:Purchase', 'View:Purchase', 'Update:Purchase', 'RecordPaymentPurchase'] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
    $this->user->givePermissionTo(['ViewAny:Purchase', 'View:Purchase', 'Update:Purchase', 'RecordPaymentPurchase']);
    actingAs($this->user);
});

describe('Purchase payment history', function () {
    it('creates a PurchasePayment row when recording a partial payment', function () {
        $supplier = Supplier::factory()->create();
        $purchase = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'total' => 1000,
            'amount_paid' => 0,
            'payment_status' => 'unpaid',
        ]);

        Livewire::test(EditPurchase::class, ['record' => $purchase->getRouteKey()])
            ->mountAction('recordPayment')
            ->setActionData([
                'amount_paid' => 400,
                'payment_method' => 'cash',
                'paid_date' => '2026-03-01',
            ])
            ->callMountedAction();

        $fresh = $purchase->fresh();

        expect((float) $fresh->amount_paid)->toBe(400.0)
            ->and($fresh->payment_status)->toBe('partial');

        $this->assertDatabaseHas('purchase_payments', [
            'purchase_id' => $purchase->id,
            'amount' => 400,
            'payment_method' => 'cash',
            'balance_after' => 600,
        ]);

        expect(PurchasePayment::where('purchase_id', $purchase->id)->count())->toBe(1);
    });

    it('records a second history row for a follow-up payment that fully pays off the purchase', function () {
        $supplier = Supplier::factory()->create();
        $purchase = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'total' => 1000,
            'amount_paid' => 400,
            'payment_status' => 'partial',
        ]);
        PurchasePayment::create([
            'purchase_id' => $purchase->id,
            'amount' => 400,
            'payment_method' => 'cash',
            'paid_date' => '2026-03-01',
            'balance_after' => 600,
        ]);

        Livewire::test(EditPurchase::class, ['record' => $purchase->getRouteKey()])
            ->mountAction('recordPayment')
            ->setActionData([
                'amount_paid' => 600,
                'payment_method' => 'bank',
                'paid_date' => '2026-04-01',
            ])
            ->callMountedAction();

        $fresh = $purchase->fresh();

        expect((float) $fresh->amount_paid)->toBe(1000.0)
            ->and($fresh->payment_status)->toBe('paid');

        expect(PurchasePayment::where('purchase_id', $purchase->id)->count())->toBe(2);

        $this->assertDatabaseHas('purchase_payments', [
            'purchase_id' => $purchase->id,
            'amount' => 600,
            'payment_method' => 'bank',
            'balance_after' => 0,
        ]);
    });
});
