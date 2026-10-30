<?php

use App\Exports\AuditLogsExport;
use App\Models\AuditLog;
use App\Models\User;

function aleLog(User $user, string $action, string $at, array $extra = []): AuditLog
{
    $log = AuditLog::create([
        'user_id' => $user->id,
        'user_name' => $user->name,
        'action' => $action,
        'ip_address' => '103.225.138.246',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36',
    ] + $extra);

    $log->forceFill(['created_at' => $at])->save();

    return $log;
}

it('summarises activity per user per day within the date range', function () {
    $cashier = User::factory()->create(['name' => 'Cashier A']);
    $manager = User::factory()->create(['name' => 'Manager B']);

    aleLog($cashier, 'login', '2026-10-29 08:00:00');
    aleLog($cashier, 'completed_sale', '2026-10-29 09:00:00');
    aleLog($cashier, 'completed_sale', '2026-10-29 10:00:00');
    aleLog($cashier, 'printed_receipt', '2026-10-29 10:01:00');
    aleLog($cashier, 'requested_void', '2026-10-29 11:00:00');
    aleLog($manager, 'approved_void_request', '2026-10-29 11:05:00');
    // Outside the range — must not be counted.
    aleLog($cashier, 'completed_sale', '2026-10-30 09:00:00');

    $data = (new AuditLogsExport(dateFrom: '2026-10-29', dateUntil: '2026-10-29', summary: true))->getData()->values();

    expect($data)->toHaveCount(3);

    $cashierRow = $data->firstWhere('User', 'Cashier A');
    expect($cashierRow)->toMatchArray([
        'Date' => '2026-10-29',
        'Total Actions' => 5,
        'Logins' => 1,
        'Sales' => 2,
        'Receipt Prints' => 1,
        'Void/Exchange Requests' => 1,
        'First Activity' => '08:00:00',
        'Last Activity' => '11:00:00',
        'IP Addresses' => '103.225.138.246',
    ]);

    expect($data->firstWhere('User', 'Manager B')['Void Approvals/Rejections'])->toBe(1)
        ->and($data->last()['Date'])->toBe('TOTAL')
        ->and($data->last()['Total Actions'])->toBe(6);
});

it('details amounts, register activity and which records were touched in the summary', function () {
    $cashier = User::factory()->create(['name' => 'Cashier A']);

    aleLog($cashier, 'opened_register', '2026-10-29 08:00:00', ['new_values' => ['opening_amount' => 1000]]);
    aleLog($cashier, 'completed_sale', '2026-10-29 09:00:00', ['new_values' => ['total' => 500, 'payment_method' => 'cash']]);
    aleLog($cashier, 'completed_sale', '2026-10-29 09:30:00', ['new_values' => ['total' => 6000, 'payment_method' => 'charge']]);
    aleLog($cashier, 'settled_invoice_payment', '2026-10-29 10:00:00', ['new_values' => ['amount_paid_now' => 4350]]);
    aleLog($cashier, 'created', '2026-10-29 11:00:00', [
        'auditable_type' => 'App\\Models\\Expense',
        'new_values' => ['amount' => '50.00'],
    ]);
    aleLog($cashier, 'created_expense', '2026-10-29 11:00:00', ['new_values' => ['amount' => '50.00']]);
    aleLog($cashier, 'updated', '2026-10-29 11:30:00', ['auditable_type' => 'App\\Models\\Inventory']);
    aleLog($cashier, 'deleted', '2026-10-29 12:00:00', ['auditable_type' => 'App\\Models\\Sale', 'auditable_label' => 'Sale #91']);
    aleLog($cashier, 'added_cash_to_register', '2026-10-29 13:00:00', ['new_values' => ['amount' => '5700.00']]);
    aleLog($cashier, 'closed_register', '2026-10-29 17:15:00', ['new_values' => ['closing_amount' => 7550, 'discrepancy' => '-20.00']]);
    aleLog($cashier, 'created_quotation', '2026-10-29 17:20:00');

    $data = (new AuditLogsExport(dateFrom: '2026-10-29', dateUntil: '2026-10-29', summary: true))->getData()->values();

    expect($data->first())->toMatchArray([
        'Hours Active' => '9:20',
        'Sales' => 2,
        'Sales Amount' => 6500.0,
        'Sales by Payment Method' => 'charge: 1 (6,000.00); cash: 1 (500.00)',
        'Payments Collected' => 1,
        'Payments Collected Amount' => 4350.0,
        'Expenses' => 1,
        'Expenses Amount' => 50.0,
        'Register Opening' => 1000.0,
        'Cash Added to Register' => 5700.0,
        'Register Closing' => 7550.0,
        'Register Discrepancy' => -20.0,
        'Created by Type' => 'Expense: 1',
        'Updated by Type' => 'Inventory: 1',
        'Records Deleted' => 1,
        'Deleted by Type' => 'Sale: 1',
        'Deleted Records' => 'Sale #91',
        'Other Actions' => 'created quotation: 1',
        'Devices' => 'Chrome · Windows · Desktop',
    ]);

    expect($data->last())->toMatchArray(['Date' => 'TOTAL', 'Sales Amount' => 6500.0, 'Deleted Records' => ''])
        ->and((new AuditLogsExport(summary: true))->getHeaders())->toBe(array_keys($data->first()));
});

it('limits the export to one user', function () {
    $cashier = User::factory()->create();
    $other = User::factory()->create();

    aleLog($cashier, 'login', '2026-10-29 08:00:00');
    aleLog($other, 'login', '2026-10-29 08:00:00');

    $rows = collect((new AuditLogsExport(userId: $cashier->id, dateFrom: '2026-10-29', dateUntil: '2026-10-29'))->getData());

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['User'])->toBe($cashier->name);
});

it('lists each entry with readable changes, IP and device in the detailed report', function () {
    $user = User::factory()->create();

    aleLog($user, 'updated', '2026-10-29 08:00:00', [
        'auditable_type' => 'App\\Models\\Product',
        'auditable_id' => 1,
        'auditable_label' => 'Product: Cement',
        'old_values' => ['price' => 250],
        'new_values' => ['price' => 260],
    ]);

    $row = collect((new AuditLogsExport(dateFrom: '2026-10-29', dateUntil: '2026-10-29'))->getData())->first();

    expect($row)->toMatchArray([
        'Timestamp' => '2026-10-29 08:00:00',
        'Action' => 'Updated',
        'Model' => 'Product',
        'Record' => 'Product: Cement',
        'Changes' => 'price: 250 → 260',
        'IP Address' => '103.225.138.246',
        'Device' => 'Chrome · Windows · Desktop',
    ]);
});

it('shows IP and device on the Audit Logs page and downloads the report', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('ViewAny:AuditLog'));
    \Pest\Laravel\actingAs($admin);

    aleLog($admin, 'login', now()->toDateTimeString());

    \Livewire\Livewire::test(\App\Filament\Resources\AuditLogs\Pages\ListAuditLogs::class)
        ->assertSee('103.225.138.246')
        ->assertSee('Chrome · Windows · Desktop')
        ->callAction('export', data: [
            'format' => 'summary',
            'date_from' => today()->toDateString(),
            'date_until' => today()->toDateString(),
        ])
        ->assertHasNoActionErrors()
        ->assertFileDownloaded();
});

it('shows readable before and after values on the audit log page', function () {
    $admin = User::factory()->create(['name' => 'Admin C']);
    $admin->givePermissionTo(collect(['View:AuditLog', 'ViewAny:AuditLog'])->map(fn ($name) => \Spatie\Permission\Models\Permission::findOrCreate($name)));
    \Pest\Laravel\actingAs($admin);

    $log = aleLog($admin, 'updated', '2026-10-29 08:00:00', [
        'auditable_type' => 'App\Models\Sale',
        'auditable_label' => 'Sale #47',
        'old_values' => ['payment_status' => 'partial', 'amount_paid' => '65000.00', 'paid_date' => '2026-09-11T16:00:00.000000Z'],
        'new_values' => ['payment_status' => 'paid', 'amount_paid' => 70154, 'paid_date' => '2026-10-10 00:00:00'],
    ]);

    \Livewire\Livewire::test(\App\Filament\Resources\AuditLogs\Pages\ViewAuditLog::class, ['record' => $log->getKey()])
        ->assertSeeInOrder(['Updated', 'Sale #47', 'Admin C'])
        ->assertSee('What changed')
        ->assertSeeInOrder(['Amount Paid', '₱65,000.00', '₱70,154.00'])
        ->assertSeeInOrder(['Paid Date', 'Sep 12, 2026', 'Oct 10, 2026'])
        ->assertSee('Partial');
});

it('names related records instead of showing bare IDs for a created record', function () {
    $admin = User::factory()->create(['name' => 'Admin C']);
    $admin->givePermissionTo(collect(['View:AuditLog', 'ViewAny:AuditLog'])->map(fn ($name) => \Spatie\Permission\Models\Permission::findOrCreate($name)));
    \Pest\Laravel\actingAs($admin);

    $log = aleLog($admin, 'created', '2026-10-29 08:00:00', [
        'auditable_type' => 'App\Models\Expense',
        'auditable_label' => 'Expense EXP-1',
        'new_values' => ['amount' => 100, 'user_id' => $admin->id, 'description' => null, 'created_at' => '2026-10-29 08:00:00'],
    ]);

    \Livewire\Livewire::test(\App\Filament\Resources\AuditLogs\Pages\ViewAuditLog::class, ['record' => $log->getKey()])
        ->assertSee('Details')
        ->assertSeeInOrder(['Amount', '₱100.00'])
        ->assertSeeInOrder(['User', 'Admin C', '#'.$admin->id])
        ->assertDontSee('Created At');
});
