<?php

use App\Enums\CashRegisterStatus;
use App\Filament\Resources\CashRegisterSessions\Pages\ListCashRegisterSessions;
use App\Models\AuditLog;
use App\Models\CashRegisterSession;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    // Reaching the Cash Register page needs the Shield view permissions.
    foreach (['admin', 'cashier'] as $role) {
        Role::findOrCreate($role)->givePermissionTo(
            Permission::findOrCreate('ViewAny:CashRegisterSession'),
            Permission::findOrCreate('View:CashRegisterSession'),
        );
    }

    $this->cashier = User::factory()->create();
    $this->cashier->assignRole('cashier');
    $this->manager = User::factory()->create();
    $this->manager->assignRole('admin');

    // An August session closed at ₱0 with ₱6,000 expected — a ₱6,000 "shortage".
    $this->session = CashRegisterSession::create([
        'user_id' => $this->cashier->id,
        'opening_amount' => 1000,
        'total_cash_sales' => 5000,
        'closing_amount' => 0,
        'expected_amount' => 6000,
        'discrepancy' => -6000,
        'opened_at' => '2026-08-14 08:00:00',
        'closed_at' => '2026-08-14 18:00:00',
        'status' => CashRegisterStatus::Closed,
    ]);
});

it('keeps the closing amount until a manager approves a cashier request', function () {
    actingAs($this->cashier);
    $correction = $this->session->requestClosingCorrection($this->cashier, 5950, 'Forgot to enter the count');

    expect($correction->status)->toBe('pending')
        ->and((float) $correction->original_closing_amount)->toBe(0.0)
        ->and((float) $this->session->fresh()->closing_amount)->toBe(0.0);
});

it('applies the corrected amount and recomputes the discrepancy on approval', function () {
    actingAs($this->cashier);
    $correction = $this->session->requestClosingCorrection($this->cashier, 5950, 'Forgot to enter the count');

    actingAs($this->manager);
    $correction->approve($this->manager);

    $session = $this->session->fresh();
    expect((float) $session->closing_amount)->toBe(5950.0)
        ->and((float) $session->discrepancy)->toBe(-50.0)
        ->and((float) $session->expected_amount)->toBe(6000.0)
        ->and($session->closed_at->toDateString())->toBe('2026-08-14')
        ->and($correction->fresh()->reviewed_by_id)->toBe($this->manager->id);

    $log = AuditLog::where('action', 'approved_closing_correction')->sole();
    expect((float) $log->old_values['closing_amount'])->toBe(0.0)
        ->and($log->new_values['requested_by'])->toBe($this->cashier->name);
});

it('leaves the session alone when rejected', function () {
    actingAs($this->cashier);
    $correction = $this->session->requestClosingCorrection($this->cashier, 5950, 'Forgot to enter the count');

    actingAs($this->manager);
    $correction->reject($this->manager, 'Count does not match the deposit slip');

    expect((float) $this->session->fresh()->closing_amount)->toBe(0.0)
        ->and($correction->fresh()->status)->toBe('rejected');
});

it('applies a manager request immediately', function () {
    actingAs($this->manager);
    $correction = $this->session->requestClosingCorrection($this->manager, 6000, 'Recounted');

    expect($correction->fresh()->status)->toBe('approved')
        ->and((float) $this->session->fresh()->discrepancy)->toBe(0.0);
});

it('refuses to review a correction twice', function () {
    actingAs($this->manager);
    $correction = $this->session->requestClosingCorrection($this->cashier, 5950, 'Forgot');
    $correction->approve($this->manager);

    expect(fn () => $correction->fresh()->reject($this->manager, null))->toThrow(RuntimeException::class);
});

it('lets a cashier request and a manager approve from the Cash Register page', function () {
    actingAs($this->cashier);
    Livewire::test(ListCashRegisterSessions::class)
        ->callTableAction('request_closing_correction', $this->session, data: [
            'corrected_amount' => 5950,
            'reason' => 'Forgot to enter the count',
        ])
        ->assertHasNoTableActionErrors();

    expect($this->session->pendingClosingCorrection())->not->toBeNull();

    actingAs($this->manager);
    Livewire::test(ListCashRegisterSessions::class)
        ->callTableAction('approve_closing_correction', $this->session);

    expect((float) $this->session->fresh()->closing_amount)->toBe(5950.0);
});

it('only shows a cashier their own sessions', function () {
    $other = User::factory()->create();
    $other->assignRole('cashier');

    actingAs($other);
    Livewire::test(ListCashRegisterSessions::class)
        ->assertCanNotSeeTableRecords([$this->session]);

    actingAs($this->manager);
    Livewire::test(ListCashRegisterSessions::class)
        ->assertCanSeeTableRecords([$this->session]);
});
