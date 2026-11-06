<?php

use App\Filament\Pages\Home;
use App\Filament\Resources\MiscellaneousExpenses\MiscellaneousExpenseResource;
use App\Filament\Resources\MiscellaneousExpenses\Pages\CreateMiscellaneousExpense;
use App\Filament\Resources\MiscellaneousExpenses\Pages\EditMiscellaneousExpense;
use App\Filament\Resources\MiscellaneousExpenses\Pages\ListMiscellaneousExpenses;
use App\Filament\Resources\MiscellaneousExpenses\Pages\ViewMiscellaneousExpense;
use App\Models\MiscellaneousExpense;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('tos'));

    $this->user = User::factory()->create();

    foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny'] as $ability) {
        Permission::firstOrCreate(['name' => "{$ability}:MiscellaneousExpense", 'guard_name' => 'web']);
        $this->user->givePermissionTo("{$ability}:MiscellaneousExpense");
    }

    actingAs($this->user);
});

it('generates sequential reference numbers per day', function () {
    $first = MiscellaneousExpense::generateReferenceNumber();
    MiscellaneousExpense::create([
        'reference_number' => $first,
        'expense_date' => now()->toDateString(),
        'description' => 'Toll',
        'amount' => 100,
    ]);

    expect($first)->toBe('MISC-'.now()->format('Ymd').'-0001')
        ->and(MiscellaneousExpense::generateReferenceNumber())->toBe('MISC-'.now()->format('Ymd').'-0002');
});

it('renders the list page with records', function () {
    $expense = MiscellaneousExpense::create([
        'reference_number' => 'MISC-TEST-0001',
        'expense_date' => now()->toDateString(),
        'description' => 'Parking fee',
        'amount' => 50,
    ]);

    Livewire::test(ListMiscellaneousExpenses::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$expense]);
});

it('creates an expense through the form, with and without a vehicle', function () {
    $vehicle = Vehicle::factory()->create(['status' => 'active']);

    Livewire::test(CreateMiscellaneousExpense::class)
        ->assertOk()
        ->fillForm([
            'expense_date' => now()->toDateString(),
            'description' => 'Car wash',
            'payee' => 'Shine Wash',
            'vehicle_id' => $vehicle->id,
            'amount' => 250,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(CreateMiscellaneousExpense::class)
        ->fillForm([
            'expense_date' => now()->toDateString(),
            'description' => 'Office snacks',
            'amount' => 120,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(MiscellaneousExpense::count())->toBe(2);

    $withVehicle = MiscellaneousExpense::where('description', 'Car wash')->first();
    expect($withVehicle->vehicle_id)->toBe($vehicle->id)
        ->and($withVehicle->user_id)->toBe($this->user->id)
        ->and($withVehicle->reference_number)->toStartWith('MISC-')
        ->and((float) $withVehicle->amount)->toBe(250.0);

    expect(MiscellaneousExpense::where('description', 'Office snacks')->first()->vehicle_id)->toBeNull();
});

it('validates required fields', function () {
    Livewire::test(CreateMiscellaneousExpense::class)
        ->fillForm(['description' => null, 'amount' => null])
        ->call('create')
        ->assertHasFormErrors(['description' => 'required', 'amount' => 'required']);
});

it('renders view and edit pages and saves changes', function () {
    $vehicle = Vehicle::factory()->create(['status' => 'active']);
    $expense = MiscellaneousExpense::create([
        'reference_number' => 'MISC-TEST-0002',
        'expense_date' => now()->toDateString(),
        'description' => 'Toll fee',
        'vehicle_id' => $vehicle->id,
        'amount' => 80,
    ]);

    Livewire::test(ViewMiscellaneousExpense::class, ['record' => $expense->getRouteKey()])->assertOk();

    Livewire::test(EditMiscellaneousExpense::class, ['record' => $expense->getRouteKey()])
        ->assertOk()
        ->assertFormSet(['plate_number_display' => $vehicle->plate_number])
        ->fillForm(['amount' => 95])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) $expense->fresh()->amount)->toBe(95.0);
});

it('shows the Miscellaneous Expenses card on the Home page', function () {
    Livewire::test(Home::class)
        ->assertOk()
        ->assertSee('Miscellaneous Expenses')
        ->assertSee(MiscellaneousExpenseResource::getUrl(), false);
});
