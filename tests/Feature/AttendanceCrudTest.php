<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Attendance CRUD Operations', function () {
    it('can create an attendance record', function () {
        actingAs($this->user);

        $employee = Employee::factory()->create();

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-10',
            'time_in' => '08:00:00',
            'time_out' => '17:00:00',
            'total_hours' => 8,
            'status' => 'present',
        ]);

        expect($attendance)->toBeInstanceOf(Attendance::class)
            ->and($attendance->status->value)->toBe('present');

        expect($attendance->fresh()->date->format('Y-m-d'))->toBe('2026-08-10');
    });

    it('can read an attendance record', function () {
        actingAs($this->user);

        $attendance = Attendance::factory()->create();

        expect(Attendance::find($attendance->id))->not->toBeNull();
    });

    it('can update an attendance record', function () {
        actingAs($this->user);

        $attendance = Attendance::factory()->create(['status' => 'present']);

        $attendance->update(['status' => 'late', 'time_in' => '09:30:00']);

        expect($attendance->fresh()->status->value)->toBe('late');
    });

    it('can delete an attendance record', function () {
        actingAs($this->user);

        $attendance = Attendance::factory()->create();
        $id = $attendance->id;

        $attendance->delete();

        $this->assertDatabaseMissing('attendances', ['id' => $id]);
    });

    it('belongs to an employee', function () {
        actingAs($this->user);

        $employee = Employee::factory()->create(['name' => 'Attendance Owner']);
        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);

        expect($attendance->employee)->toBeInstanceOf(Employee::class)
            ->and($attendance->employee->name)->toBe('Attendance Owner');
    });

    it('enforces one attendance record per employee per day', function () {
        actingAs($this->user);

        $employee = Employee::factory()->create();
        Attendance::factory()->create(['employee_id' => $employee->id, 'date' => '2026-08-10']);

        expect(fn () => Attendance::factory()->create(['employee_id' => $employee->id, 'date' => '2026-08-10']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('calculates total hours from time in and time out', function () {
        $hours = Attendance::calculateTotalHours('08:00:00', '17:00:00');

        expect($hours)->toBe(9.0);
    });

    it('returns null total hours when time out is missing', function () {
        expect(Attendance::calculateTotalHours('08:00:00', null))->toBeNull();
    });

    it('returns null total hours when time out is before time in', function () {
        expect(Attendance::calculateTotalHours('17:00:00', '08:00:00'))->toBeNull();
    });

    it('can filter by status', function () {
        actingAs($this->user);

        Attendance::factory()->count(2)->create(['status' => 'present']);
        Attendance::factory()->count(1)->absent()->create();

        expect(Attendance::where('status', 'present')->get())->toHaveCount(2)
            ->and(Attendance::where('status', 'absent')->get())->toHaveCount(1);
    });
});
