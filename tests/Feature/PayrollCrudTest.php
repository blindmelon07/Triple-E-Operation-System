<?php

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Payroll CRUD Operations', function () {
    it('can create a payroll', function () {
        actingAs($this->user);

        $payroll = Payroll::create([
            'payroll_number' => Payroll::generatePayrollNumber(),
            'pay_period_start' => '2026-08-01',
            'pay_period_end' => '2026-08-15',
            'pay_period_type' => 'semi_monthly',
            'status' => 'draft',
            'generated_by' => $this->user->id,
            'total_gross' => 0,
            'total_deductions' => 0,
            'total_net' => 0,
        ]);

        expect($payroll)->toBeInstanceOf(Payroll::class)
            ->and($payroll->status->value)->toBe('draft');

        $this->assertDatabaseHas('payrolls', ['pay_period_type' => 'semi_monthly']);
    });

    it('can read a payroll', function () {
        actingAs($this->user);

        $payroll = Payroll::factory()->create();

        expect(Payroll::find($payroll->id))->not->toBeNull();
    });

    it('can update a payroll', function () {
        actingAs($this->user);

        $payroll = Payroll::factory()->create(['notes' => null]);

        $payroll->update(['notes' => 'Updated note']);

        expect($payroll->fresh()->notes)->toBe('Updated note');
    });

    it('can delete a payroll', function () {
        actingAs($this->user);

        $payroll = Payroll::factory()->create();
        $id = $payroll->id;

        $payroll->delete();

        $this->assertDatabaseMissing('payrolls', ['id' => $id]);
    });

    it('belongs to the user who generated it and the user who approved it', function () {
        actingAs($this->user);

        $generator = User::factory()->create(['name' => 'Generator']);
        $approver = User::factory()->create(['name' => 'Approver']);

        $payroll = Payroll::factory()->create([
            'generated_by' => $generator->id,
            'approved_by' => $approver->id,
        ]);

        expect($payroll->generatedBy->name)->toBe('Generator')
            ->and($payroll->approvedBy->name)->toBe('Approver');
    });

    it('generates unique, sequential payroll numbers', function () {
        actingAs($this->user);

        $number1 = Payroll::generatePayrollNumber();
        Payroll::factory()->create(['payroll_number' => $number1]);
        $number2 = Payroll::generatePayrollNumber();

        expect($number1)->not->toBe($number2)
            ->and($number1)->toStartWith('PAY-')
            ->and($number2)->toStartWith('PAY-');
    });

    it('only allows approval while in draft status', function () {
        actingAs($this->user);

        $draft = Payroll::factory()->create();
        $approved = Payroll::factory()->approved()->create();

        expect($draft->canBeApproved())->toBeTrue()
            ->and($approved->canBeApproved())->toBeFalse();
    });

    it('only allows marking as paid once approved', function () {
        actingAs($this->user);

        $draft = Payroll::factory()->create();
        $approved = Payroll::factory()->approved()->create();

        expect($draft->canBePaid())->toBeFalse()
            ->and($approved->canBePaid())->toBeTrue();
    });

    it('allows cancellation only while draft or approved, never once paid', function () {
        actingAs($this->user);

        $draft = Payroll::factory()->create();
        $approved = Payroll::factory()->approved()->create();
        $paid = Payroll::factory()->paid()->create();

        expect($draft->canBeCancelled())->toBeTrue()
            ->and($approved->canBeCancelled())->toBeTrue()
            ->and($paid->canBeCancelled())->toBeFalse();
    });

    it('recalculates totals from its payroll items', function () {
        actingAs($this->user);

        $payroll = Payroll::factory()->create([
            'total_gross' => 0, 'total_deductions' => 0, 'total_net' => 0,
        ]);

        $employeeOne = Employee::factory()->create();
        $employeeTwo = Employee::factory()->create();

        PayrollItem::factory()->create([
            'payroll_id' => $payroll->id, 'employee_id' => $employeeOne->id,
            'gross_pay' => 5000, 'total_deductions' => 500, 'net_pay' => 4500,
        ]);
        PayrollItem::factory()->create([
            'payroll_id' => $payroll->id, 'employee_id' => $employeeTwo->id,
            'gross_pay' => 6000, 'total_deductions' => 600, 'net_pay' => 5400,
        ]);

        $payroll->recalculateTotals();

        $fresh = $payroll->fresh();
        expect((float) $fresh->total_gross)->toBe(11000.0)
            ->and((float) $fresh->total_deductions)->toBe(1100.0)
            ->and((float) $fresh->total_net)->toBe(9900.0);
    });

    it('has many payroll items', function () {
        actingAs($this->user);

        $payroll = Payroll::factory()->create();
        PayrollItem::factory()->count(3)->create(['payroll_id' => $payroll->id]);

        expect($payroll->payrollItems)->toHaveCount(3);
    });
});
