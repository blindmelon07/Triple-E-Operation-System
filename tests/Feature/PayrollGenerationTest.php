<?php

use App\Filament\Resources\Payrolls\Pages\CreatePayroll;
use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\GovernmentContribution;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->admin = User::factory()->create();

    // CreateRecord pages check both create and the resource's viewAny
    // (entering any resource route requires canAccess(), which is
    // canViewAny()) — granting only Create:Payroll 403s on mount.
    Permission::firstOrCreate(['name' => 'Create:Payroll', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'ViewAny:Payroll', 'guard_name' => 'web']);
    $this->admin->givePermissionTo(['Create:Payroll', 'ViewAny:Payroll']);

    actingAs($this->admin);

    // Livewire::test() mounts the page component directly, bypassing the
    // panel routing middleware that would normally set this — without it,
    // Filament's form-testing helpers (fillForm/assertHasNoFormErrors)
    // blow up looking for a current panel that was never set.
    Filament::setCurrentPanel(Filament::getPanel('tos'));
});

describe('Payroll generation (CreatePayroll::afterCreate)', function () {
    it('computes days worked, late deduction, absences, paid leave, and halved government deductions', function () {
        // Government contribution brackets wide enough to cover any
        // reasonable monthly-equivalent salary used in this test.
        GovernmentContribution::factory()->sss()->create([
            'salary_from' => 0, 'salary_to' => 999999, 'employee_share' => 100,
        ]);
        GovernmentContribution::factory()->philhealth()->create([
            'salary_from' => 0, 'salary_to' => 999999, 'employee_share' => 50,
        ]);
        GovernmentContribution::factory()->pagibig()->create([
            'salary_from' => 0, 'salary_to' => 999999, 'employee_share' => 20,
        ]);

        $employee = Employee::factory()->create(['name' => 'Test Employee']);

        EmployeeCompensation::factory()->create([
            'employee_id' => $employee->id,
            'daily_rate' => 500,
            'pay_period' => 'semi_monthly',
            'days_off' => ['sunday'],
            'sss_enabled' => true,
            'philhealth_enabled' => true,
            'pagibig_enabled' => true,
            'late_deduction_type' => 'per_minute',
            'late_deduction_amount' => 0,
            'allowance' => 0,
        ]);

        // Period: Mon Aug 3 - Fri Aug 7, 2026 (no Sunday in range, so every
        // day is a working day for this employee).
        $employee->attendances()->create([
            'date' => '2026-08-03', 'time_in' => '08:00:00', 'time_out' => '17:00:00',
            'total_hours' => 8, 'status' => 'present',
        ]);
        $employee->attendances()->create([
            // 20 minutes past the 9:00 AM late threshold.
            'date' => '2026-08-04', 'time_in' => '09:20:00', 'time_out' => '17:00:00',
            'total_hours' => 7.67, 'status' => 'late',
        ]);
        $employee->attendances()->create([
            'date' => '2026-08-05', 'time_in' => null, 'time_out' => null,
            'total_hours' => null, 'status' => 'absent',
        ]);
        $employee->attendances()->create([
            'date' => '2026-08-06', 'time_in' => '08:00:00', 'time_out' => '12:00:00',
            'total_hours' => 4, 'status' => 'half_day',
        ]);
        // Aug 7: no attendance record, but an approved paid leave request
        // covering that day should still count as a worked day.
        $leaveType = LeaveType::factory()->create(['is_paid' => true]);
        LeaveRequest::factory()->approved()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-07',
            'end_date' => '2026-08-07',
            'total_days' => 1,
        ]);

        Livewire::test(CreatePayroll::class)
            ->fillForm([
                'pay_period_type' => 'semi_monthly',
                'pay_period_start' => '2026-08-03',
                'pay_period_end' => '2026-08-07',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $payroll = Payroll::latest('id')->first();
        expect($payroll)->not->toBeNull()
            ->and($payroll->status->value)->toBe('draft')
            ->and($payroll->generated_by)->toBe($this->admin->id);

        $item = PayrollItem::where('payroll_id', $payroll->id)
            ->where('employee_id', $employee->id)
            ->first();
        expect($item)->not->toBeNull();

        // Days worked: present (1) + late (1) + half day (0.5) + paid leave (1) = 3.5
        expect((float) $item->days_worked)->toBe(3.5)
            ->and((float) $item->days_absent)->toBe(1.0)
            ->and($item->late_count)->toBe(1)
            ->and($item->late_minutes)->toBe(20);

        // Gross = 500 * 3.5 = 1750
        expect((float) $item->gross_pay)->toBe(1750.0);

        // Late deduction (per_minute): (500 / 480) * 20 = 20.83
        expect((float) $item->late_deduction)->toBe(20.83);

        // Semi-monthly halves the government deduction brackets. PhilHealth
        // is the exception: CreatePayroll overrides the bracket lookup with
        // a flat 2.5% of the monthly-equivalent salary whenever that salary
        // exceeds ₱10,000 (13000 here), ignoring the configured bracket —
        // round(13000 * 0.025, 2) = 325, halved to 162.5.
        expect((float) $item->sss_deduction)->toBe(50.0)
            ->and((float) $item->philhealth_deduction)->toBe(162.5)
            ->and((float) $item->pagibig_deduction)->toBe(10.0);

        // Net = 1750 - (20.83 + 50 + 162.5 + 10) = 1506.67
        expect((float) $item->total_deductions)->toBe(243.33)
            ->and((float) $item->net_pay)->toBe(1506.67);

        // Payroll totals mirror the single item since there's one employee.
        expect((float) $payroll->fresh()->total_gross)->toBe(1750.0)
            ->and((float) $payroll->fresh()->total_net)->toBe(1506.67);
    });

    it('excludes a rest day from working days so it never counts as absent', function () {
        $employee = Employee::factory()->create();

        EmployeeCompensation::factory()->create([
            'employee_id' => $employee->id,
            'daily_rate' => 500,
            'pay_period' => 'weekly',
            'days_off' => ['sunday'],
            'sss_enabled' => false,
            'philhealth_enabled' => false,
            'pagibig_enabled' => false,
        ]);

        // Period: Sun Aug 2 - Sat Aug 8, 2026. Only Sunday has no attendance
        // record and it should be silently excluded, not counted absent.
        $employee->attendances()->create([
            'date' => '2026-08-03', 'time_in' => '08:00:00', 'time_out' => '17:00:00',
            'total_hours' => 8, 'status' => 'present',
        ]);

        Livewire::test(CreatePayroll::class)
            ->fillForm([
                'pay_period_type' => 'weekly',
                'pay_period_start' => '2026-08-02',
                'pay_period_end' => '2026-08-08',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $payroll = Payroll::latest('id')->first();
        $item = PayrollItem::where('payroll_id', $payroll->id)
            ->where('employee_id', $employee->id)
            ->first();

        expect((float) $item->days_absent)->toBe(0.0)
            ->and((float) $item->days_worked)->toBe(1.0);
    });

    it('only includes employees whose compensation pay period matches the payroll', function () {
        $matching = Employee::factory()->create(['name' => 'Matching Employee']);
        EmployeeCompensation::factory()->create([
            'employee_id' => $matching->id,
            'pay_period' => 'semi_monthly',
        ]);

        $nonMatching = Employee::factory()->create(['name' => 'Weekly Employee']);
        EmployeeCompensation::factory()->create([
            'employee_id' => $nonMatching->id,
            'pay_period' => 'weekly',
        ]);

        Livewire::test(CreatePayroll::class)
            ->fillForm([
                'pay_period_type' => 'semi_monthly',
                'pay_period_start' => '2026-08-01',
                'pay_period_end' => '2026-08-15',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $payroll = Payroll::latest('id')->first();

        expect(PayrollItem::where('payroll_id', $payroll->id)->where('employee_id', $matching->id)->exists())->toBeTrue()
            ->and(PayrollItem::where('payroll_id', $payroll->id)->where('employee_id', $nonMatching->id)->exists())->toBeFalse();
    });
});
