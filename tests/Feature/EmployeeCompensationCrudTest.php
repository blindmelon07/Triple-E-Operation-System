<?php

use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('EmployeeCompensation CRUD Operations', function () {
    it('can create a compensation record', function () {
        actingAs($this->user);

        $employee = Employee::factory()->create();

        $compensation = EmployeeCompensation::create([
            'employee_id' => $employee->id,
            'daily_rate' => 600,
            'pay_period' => 'semi_monthly',
            'days_off' => ['sunday'],
            'sss_enabled' => true,
            'philhealth_enabled' => true,
            'pagibig_enabled' => true,
            'overtime_rate_multiplier' => 1.25,
            'late_deduction_type' => 'per_minute',
            'late_deduction_amount' => 0,
            'allowance' => 0,
        ]);

        expect($compensation)->toBeInstanceOf(EmployeeCompensation::class)
            ->and((float) $compensation->daily_rate)->toBe(600.0);

        $this->assertDatabaseHas('employee_compensations', ['employee_id' => $employee->id]);
    });

    it('can read a compensation record', function () {
        actingAs($this->user);

        $compensation = EmployeeCompensation::factory()->create();

        expect(EmployeeCompensation::find($compensation->id))->not->toBeNull();
    });

    it('can update a compensation record', function () {
        actingAs($this->user);

        $compensation = EmployeeCompensation::factory()->create(['daily_rate' => 500]);

        $compensation->update(['daily_rate' => 750]);

        expect((float) $compensation->fresh()->daily_rate)->toBe(750.0);
    });

    it('can delete a compensation record', function () {
        actingAs($this->user);

        $compensation = EmployeeCompensation::factory()->create();
        $id = $compensation->id;

        $compensation->delete();

        $this->assertDatabaseMissing('employee_compensations', ['id' => $id]);
    });

    it('belongs to an employee', function () {
        actingAs($this->user);

        $employee = Employee::factory()->create(['name' => 'Comp Test']);
        $compensation = EmployeeCompensation::factory()->create(['employee_id' => $employee->id]);

        expect($compensation->employee)->toBeInstanceOf(Employee::class)
            ->and($compensation->employee->name)->toBe('Comp Test');
    });

    it('computes the monthly equivalent as daily rate times 26', function () {
        actingAs($this->user);

        $compensation = EmployeeCompensation::factory()->create(['daily_rate' => 500]);

        expect($compensation->getMonthlyEquivalent())->toBe(13000.0);
    });

    it('enforces one compensation record per employee', function () {
        actingAs($this->user);

        $employee = Employee::factory()->create();
        EmployeeCompensation::factory()->create(['employee_id' => $employee->id]);

        expect(fn () => EmployeeCompensation::factory()->create(['employee_id' => $employee->id]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('casts days off to an array', function () {
        actingAs($this->user);

        $compensation = EmployeeCompensation::factory()->create(['days_off' => ['saturday', 'sunday']]);

        expect($compensation->fresh()->days_off)->toBe(['saturday', 'sunday']);
    });
});
