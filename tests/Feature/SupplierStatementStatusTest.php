<?php

use App\Filament\Pages\CustomReportBuilder;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'View:CustomReportBuilder', 'guard_name' => 'web']);
    $user->givePermissionTo('View:CustomReportBuilder');
    actingAs($user);

    $this->supplier = Supplier::factory()->create();

    foreach (['paid' => 'SI-PAID', 'partial' => 'SI-PART', 'unpaid' => 'SI-UNPAID'] as $status => $si) {
        Purchase::factory()->create([
            'supplier_id' => $this->supplier->id,
            'date' => '2026-10-02',
            'si_number' => $si,
            'payment_status' => $status,
        ]);
    }
});

function generatedStatement(int $supplierId)
{
    return Livewire::test(CustomReportBuilder::class)
        ->set('reportMode', 'supplier_statement')
        ->set('supplierId', $supplierId)
        ->set('statementDateFrom', '2026-10-01')
        ->set('statementDateTo', '2026-10-31')
        ->call('generateStatement');
}

it('shows a paid/partial/unpaid status per purchase on the accounts payable report', function () {
    generatedStatement($this->supplier->id)
        ->assertOk()
        ->assertSee('Status')
        ->assertSeeInOrder(['SI-PAID', 'Paid'])
        ->assertSeeInOrder(['SI-PART', 'Partial'])
        ->assertSeeInOrder(['SI-UNPAID', 'Unpaid']);
});

it('includes the status column in the CSV and PDF exports', function () {
    $component = generatedStatement($this->supplier->id);

    $csv = $component->instance()->exportStatementCsv();
    ob_start();
    $csv->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('Status')
        ->and($content)->toContain('Paid')
        ->and($content)->toContain('Partial')
        ->and($content)->toContain('Unpaid');

    $component->callAction('exportStatementPdf')->assertFileDownloaded();
});
