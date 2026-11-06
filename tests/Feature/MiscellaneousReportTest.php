<?php

use App\Filament\Pages\MiscellaneousReport;
use App\Models\MiscellaneousExpense;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\ReportBuilder\MiscellaneousReportService;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'View:MiscellaneousReport', 'guard_name' => 'web']);
    $user->givePermissionTo('View:MiscellaneousReport');
    actingAs($user);

    $this->vehicle = Vehicle::factory()->create();

    $make = fn (string $date, string $description, float $amount, ?int $vehicleId = null) => MiscellaneousExpense::create([
        'reference_number' => 'MISC-'.$description,
        'expense_date' => $date,
        'description' => $description,
        'vehicle_id' => $vehicleId,
        'amount' => $amount,
    ]);

    $make('2026-10-05', 'Toll fee', 100, $this->vehicle->id);
    $make('2026-10-10', 'Office snacks', 250);
    $make('2026-10-20', 'Car wash', 150, $this->vehicle->id);
    $make('2026-11-02', 'Parking', 40, $this->vehicle->id); // outside the October range
});

it('builds a date-filtered ledger with a running total', function () {
    $result = (new MiscellaneousReportService)->build('2026-10-01', '2026-10-31');

    expect($result['rows']->pluck('description')->all())->toBe(['Toll fee', 'Office snacks', 'Car wash'])
        ->and($result['rows']->pluck('running_total')->all())->toBe([100.0, 350.0, 500.0])
        ->and($result['totals'])->toBe(['count' => 3, 'amount' => 500.0]);
});

it('filters by vehicle', function () {
    $result = (new MiscellaneousReportService)->build('2026-10-01', '2026-10-31', $this->vehicle->id);

    expect($result['rows']->pluck('description')->all())->toBe(['Toll fee', 'Car wash'])
        ->and($result['totals']['amount'])->toBe(250.0);
});

it('renders the report page and downloads CSV and PDF', function () {
    Livewire::test(MiscellaneousReport::class)
        ->assertOk()
        ->set('dateFrom', '2026-10-01')
        ->set('dateTo', '2026-10-31')
        ->call('generate')
        ->assertSee('Office snacks')
        ->assertDontSee('Parking')
        ->assertSee('500.00')
        ->callAction('exportCsv')
        ->assertFileDownloaded()
        ->callAction('exportPdf')
        ->assertFileDownloaded();
});

it('shows an empty state when nothing matches', function () {
    Livewire::test(MiscellaneousReport::class)
        ->set('dateFrom', '2025-01-01')
        ->set('dateTo', '2025-01-31')
        ->call('generate')
        ->assertSee('No miscellaneous expenses found for those filters.');
});
