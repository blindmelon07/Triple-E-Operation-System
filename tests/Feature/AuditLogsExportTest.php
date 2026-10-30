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
