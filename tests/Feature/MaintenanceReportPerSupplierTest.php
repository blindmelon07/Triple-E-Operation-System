<?php

use App\Filament\Pages\MaintenanceReport;
use App\Models\MaintenanceRecord;
use App\Models\Supplier;
use App\Models\User;
use App\Support\ReportBuilder\MaintenanceReportService;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('MaintenanceReportService::buildPerSupplier', function () {
    it('groups records per supplier with running totals and subtotals, no-supplier last', function () {
        $zeta = Supplier::factory()->create(['name' => 'Zeta Auto Parts']);
        $alpha = Supplier::factory()->create(['name' => 'Alpha Motors']);

        MaintenanceRecord::factory()->create(['supplier_id' => $zeta->id, 'maintenance_date' => '2026-09-05', 'cost' => 300]);
        MaintenanceRecord::factory()->create(['supplier_id' => $alpha->id, 'maintenance_date' => '2026-09-10', 'cost' => 200]);
        MaintenanceRecord::factory()->create(['supplier_id' => $alpha->id, 'maintenance_date' => '2026-09-02', 'cost' => 100]);
        MaintenanceRecord::factory()->create(['supplier_id' => null, 'maintenance_date' => '2026-09-03', 'cost' => 50]);
        MaintenanceRecord::factory()->create(['supplier_id' => $alpha->id, 'maintenance_date' => '2026-08-01', 'cost' => 9999]); // outside range

        $result = (new MaintenanceReportService)->buildPerSupplier('2026-09-01', '2026-09-30');

        expect(array_column($result['groups'], 'supplier'))->toBe(['Alpha Motors', 'Zeta Auto Parts', 'No Supplier'])
            ->and($result['groups'][0]['count'])->toBe(2)
            ->and($result['groups'][0]['subtotal'])->toBe(300.0)
            ->and(array_column($result['groups'][0]['rows'], 'running_total'))->toBe([100.0, 300.0])
            ->and($result['groups'][1]['subtotal'])->toBe(300.0)
            ->and($result['groups'][2]['subtotal'])->toBe(50.0)
            ->and($result['totals']['amount'])->toBe(650.0);
    });

    it('limits the report to one supplier when filtered', function () {
        $a = Supplier::factory()->create();
        $b = Supplier::factory()->create();
        MaintenanceRecord::factory()->create(['supplier_id' => $a->id, 'cost' => 100]);
        MaintenanceRecord::factory()->create(['supplier_id' => $b->id, 'cost' => 200]);

        $grouped = (new MaintenanceReportService)->buildPerSupplier(supplierId: $b->id);
        $itemized = (new MaintenanceReportService)->build(supplierId: $b->id);

        expect($grouped['groups'])->toHaveCount(1)
            ->and($grouped['totals']['amount'])->toBe(200.0)
            ->and($itemized['rows'])->toHaveCount(1)
            ->and($itemized['totals']['amount'])->toBe(200.0);
    });
});

it('generates and exports the per-supplier Maintenance Report', function (string $action) {
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'View:MaintenanceReport', 'guard_name' => 'web']);
    $user->givePermissionTo('View:MaintenanceReport');
    actingAs($user);

    $supplier = Supplier::factory()->create();
    MaintenanceRecord::factory()->create(['supplier_id' => $supplier->id, 'maintenance_date' => now(), 'cost' => 500]);

    Livewire::test(MaintenanceReport::class)
        ->set('viewMode', 'per_supplier')
        ->call('generate')
        ->assertCount('groups', 1)
        ->assertSee($supplier->name)
        ->callAction($action)
        ->assertFileDownloaded();
})->with(['exportCsv', 'exportPdf']);
