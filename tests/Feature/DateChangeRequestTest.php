<?php

use App\Filament\Resources\DateChangeRequests\Pages\ListDateChangeRequests;
use App\Models\AuditLog;
use App\Models\DateChangeRequest;
use App\Models\MaintenanceRecord;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Role::findOrCreate('admin');

    $this->encoder = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->record = MaintenanceRecord::factory()->create(['maintenance_date' => '2026-09-29']);
});

it('only lets admins approve date changes', function () {
    expect(DateChangeRequest::canApprove($this->admin))->toBeTrue()
        ->and(DateChangeRequest::canApprove($this->encoder))->toBeFalse();
});

it('files a pending request without touching the date, and logs it', function () {
    actingAs($this->encoder);

    $request = DateChangeRequest::submit($this->record, 'maintenance_date', '2026-09-15', 'Encoded late');

    expect($request->status)->toBe('pending')
        ->and($request->old_date->toDateString())->toBe('2026-09-29')
        ->and($this->record->fresh()->maintenance_date->toDateString())->toBe('2026-09-29');

    $log = AuditLog::where('action', 'requested_date_change')->sole();
    expect($log->user_id)->toBe($this->encoder->id)
        ->and($log->auditable_id)->toBe($this->record->id)
        ->and($log->old_values)->toBe(['maintenance_date' => '2026-09-29'])
        ->and($log->new_values['maintenance_date'])->toBe('2026-09-15')
        ->and($log->new_values['reason'])->toBe('Encoded late');
});

it('applies the new date when an admin approves, and logs it', function () {
    actingAs($this->encoder);
    $request = DateChangeRequest::submit($this->record, 'maintenance_date', '2026-09-15', 'Encoded late');

    actingAs($this->admin);
    $request->approve();

    expect($this->record->fresh()->maintenance_date->toDateString())->toBe('2026-09-15')
        ->and($request->fresh()->status)->toBe('approved')
        ->and($request->fresh()->reviewed_by_id)->toBe($this->admin->id);

    $log = AuditLog::where('action', 'approved_date_change')->sole();
    expect($log->user_id)->toBe($this->admin->id)
        ->and($log->new_values['requested_by'])->toBe($this->encoder->name);

    // The record's own change is audited too.
    expect(AuditLog::where('action', 'updated')->where('auditable_id', $this->record->id)->exists())->toBeTrue();
});

it('leaves the date alone when an admin rejects, and logs it', function () {
    actingAs($this->encoder);
    $request = DateChangeRequest::submit($this->record, 'maintenance_date', '2026-09-15', 'Encoded late');

    actingAs($this->admin);
    $request->reject('Wrong SI date');

    expect($this->record->fresh()->maintenance_date->toDateString())->toBe('2026-09-29')
        ->and($request->fresh()->status)->toBe('rejected')
        ->and($request->fresh()->rejection_reason)->toBe('Wrong SI date');

    expect(AuditLog::where('action', 'rejected_date_change')->sole()->new_values['rejection_reason'])
        ->toBe('Wrong SI date');
});

it('refuses to review a request twice', function () {
    actingAs($this->encoder);
    $request = DateChangeRequest::submit($this->record, 'maintenance_date', '2026-09-15', 'Encoded late');

    actingAs($this->admin);
    $request->approve();

    expect(fn () => $request->fresh()->reject(null))->toThrow(RuntimeException::class);
});

it('lets an admin approve from the Date Change Requests page', function () {
    actingAs($this->encoder);
    $request = DateChangeRequest::submit($this->record, 'maintenance_date', '2026-09-15', 'Encoded late');

    actingAs($this->admin);
    Livewire::test(ListDateChangeRequests::class)
        ->assertCanSeeTableRecords([$request])
        ->callTableAction('approve', $request);

    expect($this->record->fresh()->maintenance_date->toDateString())->toBe('2026-09-15');
});

it('shows non-admins only their own requests, with no approve button', function () {
    $other = User::factory()->create();

    actingAs($this->encoder);
    $mine = DateChangeRequest::submit($this->record, 'maintenance_date', '2026-09-15', 'Encoded late');

    actingAs($other);
    $theirs = DateChangeRequest::submit(MaintenanceRecord::factory()->create(), 'maintenance_date', '2026-09-10', 'Typo');

    actingAs($this->encoder);
    Livewire::test(ListDateChangeRequests::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs])
        ->assertTableActionHidden('approve', $mine);
});
