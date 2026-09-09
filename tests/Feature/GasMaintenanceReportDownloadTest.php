<?php

use App\Filament\Pages\GasReport;
use App\Filament\Pages\MaintenanceReport;
use App\Models\FuelLog;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceType;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vehicle;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function grantAndActAsForGasMaintenance(string $permission): void
{
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    $user->givePermissionTo($permission);
    actingAs($user);
}

it('downloads the Gas Report CSV via its Livewire action', function () {
    grantAndActAsForGasMaintenance('View:GasReport');

    $vehicle = Vehicle::factory()->create();
    FuelLog::factory()->create(['vehicle_id' => $vehicle->id, 'fuel_date' => now(), 'cost' => 1000]);

    Livewire::test(GasReport::class)
        ->call('generate')
        ->callAction('exportCsv')
        ->assertFileDownloaded();
});

it('downloads the Gas Report PDF via its Livewire action', function () {
    grantAndActAsForGasMaintenance('View:GasReport');

    $vehicle = Vehicle::factory()->create();
    FuelLog::factory()->create(['vehicle_id' => $vehicle->id, 'fuel_date' => now(), 'cost' => 1000]);

    Livewire::test(GasReport::class)
        ->call('generate')
        ->callAction('exportPdf')
        ->assertFileDownloaded();
});

it('downloads the Maintenance Report CSV via its Livewire action', function () {
    grantAndActAsForGasMaintenance('View:MaintenanceReport');

    $vehicle = Vehicle::factory()->create();
    $type = MaintenanceType::factory()->create();
    $supplier = Supplier::factory()->create();
    MaintenanceRecord::factory()->create([
        'vehicle_id' => $vehicle->id,
        'maintenance_type_id' => $type->id,
        'supplier_id' => $supplier->id,
        'maintenance_date' => now(),
        'cost' => 500,
    ]);

    Livewire::test(MaintenanceReport::class)
        ->call('generate')
        ->callAction('exportCsv')
        ->assertFileDownloaded();
});

it('downloads the Maintenance Report PDF via its Livewire action', function () {
    grantAndActAsForGasMaintenance('View:MaintenanceReport');

    $vehicle = Vehicle::factory()->create();
    $type = MaintenanceType::factory()->create();
    $supplier = Supplier::factory()->create();
    MaintenanceRecord::factory()->create([
        'vehicle_id' => $vehicle->id,
        'maintenance_type_id' => $type->id,
        'supplier_id' => $supplier->id,
        'maintenance_date' => now(),
        'cost' => 500,
    ]);

    Livewire::test(MaintenanceReport::class)
        ->call('generate')
        ->callAction('exportPdf')
        ->assertFileDownloaded();
});
