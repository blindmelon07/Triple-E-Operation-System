<?php

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('LeaveRequest CRUD Operations', function () {
    it('can create a leave request', function () {
        actingAs($this->user);

        $employee = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create();

        $leaveRequest = LeaveRequest::create([
            'request_number' => LeaveRequest::generateRequestNumber(),
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-03',
            'total_days' => 3,
            'reason' => 'Family event',
            'status' => 'pending',
        ]);

        expect($leaveRequest)->toBeInstanceOf(LeaveRequest::class)
            ->and($leaveRequest->status->value)->toBe('pending');

        $this->assertDatabaseHas('leave_requests', ['reason' => 'Family event']);
    });

    it('can read a leave request', function () {
        actingAs($this->user);

        $leaveRequest = LeaveRequest::factory()->create();

        expect(LeaveRequest::find($leaveRequest->id))->not->toBeNull();
    });

    it('can update a leave request', function () {
        actingAs($this->user);

        $leaveRequest = LeaveRequest::factory()->create(['status' => 'pending']);

        $leaveRequest->update(['status' => 'approved', 'approved_at' => now()]);

        expect($leaveRequest->fresh()->status->value)->toBe('approved');
    });

    it('can delete a leave request', function () {
        actingAs($this->user);

        $leaveRequest = LeaveRequest::factory()->create();
        $id = $leaveRequest->id;

        $leaveRequest->delete();

        $this->assertDatabaseMissing('leave_requests', ['id' => $id]);
    });

    it('belongs to an employee and a leave type', function () {
        actingAs($this->user);

        $employee = Employee::factory()->create(['name' => 'Leave Owner']);
        $leaveType = LeaveType::factory()->create(['name' => 'Vacation Leave']);
        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);

        expect($leaveRequest->employee->name)->toBe('Leave Owner')
            ->and($leaveRequest->leaveType->name)->toBe('Vacation Leave');
    });

    it('generates unique, sequential request numbers', function () {
        actingAs($this->user);

        $ref1 = LeaveRequest::generateRequestNumber();
        LeaveRequest::factory()->create(['request_number' => $ref1]);
        $ref2 = LeaveRequest::generateRequestNumber();

        expect($ref1)->not->toBe($ref2)
            ->and($ref1)->toStartWith('LR-')
            ->and($ref2)->toStartWith('LR-');
    });

    it('can only be approved or rejected while pending', function () {
        actingAs($this->user);

        $pending = LeaveRequest::factory()->create(['status' => 'pending']);
        $approved = LeaveRequest::factory()->approved()->create();

        expect($pending->canBeApproved())->toBeTrue()
            ->and($pending->canBeRejected())->toBeTrue()
            ->and($pending->isPending())->toBeTrue()
            ->and($approved->canBeApproved())->toBeFalse()
            ->and($approved->canBeRejected())->toBeFalse()
            ->and($approved->isPending())->toBeFalse();
    });

    it('can filter by status', function () {
        actingAs($this->user);

        LeaveRequest::factory()->count(2)->create(['status' => 'pending']);
        LeaveRequest::factory()->approved()->count(1)->create();
        LeaveRequest::factory()->rejected()->count(1)->create();

        expect(LeaveRequest::where('status', 'pending')->get())->toHaveCount(2)
            ->and(LeaveRequest::where('status', 'approved')->get())->toHaveCount(1)
            ->and(LeaveRequest::where('status', 'rejected')->get())->toHaveCount(1);
    });
});
