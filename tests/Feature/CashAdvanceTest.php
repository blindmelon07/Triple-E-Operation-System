<?php

use App\Enums\PayrollStatus;
use App\Filament\Pages\CashAdvanceReport;
use App\Filament\Resources\CashAdvances\Pages\ListCashAdvances;
use App\Filament\Resources\CashAdvances\Pages\ViewCashAdvance;
use App\Filament\Resources\CashAdvances\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Payrolls\Pages\CreatePayroll;
use App\Models\CashAdvance;
use App\Models\CashAdvancePayment;
use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use App\Support\ReportBuilder\CashAdvanceReportService;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->admin = User::factory()->create();

    foreach (['Create:Payroll', 'ViewAny:Payroll', 'View:CashAdvanceReport'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
    $this->admin->givePermissionTo(['Create:Payroll', 'ViewAny:Payroll', 'View:CashAdvanceReport']);

    actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('tos'));
});

/**
 * Employee on a 500/day weekly rate with no government deductions, with
 * a present attendance record on each given date.
 *
 * @param  array<int, string>  $presentDates
 */
function cashAdvanceEmployee(array $presentDates): Employee
{
    $employee = Employee::factory()->create();

    EmployeeCompensation::factory()->create([
        'employee_id' => $employee->id,
        'daily_rate' => 500,
        'pay_period' => 'weekly',
        'days_off' => ['sunday'],
        'sss_enabled' => false,
        'philhealth_enabled' => false,
        'pagibig_enabled' => false,
        'allowance' => 0,
    ]);

    foreach ($presentDates as $date) {
        $employee->attendances()->create([
            'date' => $date, 'time_in' => '08:00:00', 'time_out' => '17:00:00',
            'total_hours' => 8, 'status' => 'present',
        ]);
    }

    return $employee;
}

function generateWeeklyPayroll(string $start, string $end): Payroll
{
    Livewire::test(CreatePayroll::class)
        ->fillForm([
            'pay_period_type' => 'weekly',
            'pay_period_start' => $start,
            'pay_period_end' => $end,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    return Payroll::latest('id')->first();
}

describe('Payroll cash advance deductions', function () {
    it('deducts the per-payroll installment and records a repayment', function () {
        // 3 days * 500 = 1500 gross
        $employee = cashAdvanceEmployee(['2026-08-03', '2026-08-04', '2026-08-05']);
        $advance = CashAdvance::factory()->create([
            'employee_id' => $employee->id,
            'date_granted' => '2026-07-20',
            'amount' => 3000,
            'deduction_per_payroll' => 1000,
        ]);

        $payroll = generateWeeklyPayroll('2026-08-02', '2026-08-08');
        $item = PayrollItem::where('payroll_id', $payroll->id)->first();

        expect((float) $item->cash_advance_deduction)->toBe(1000.0)
            ->and((float) $item->total_deductions)->toBe(1000.0)
            ->and((float) $item->net_pay)->toBe(500.0)
            ->and($advance->balance())->toBe(2000.0);

        $payment = CashAdvancePayment::first();
        expect($payment->payroll_item_id)->toBe($item->id)
            ->and($payment->payment_date->toDateString())->toBe('2026-08-08');
    });

    it('never deducts more than the remaining balance or the net pay', function () {
        // 1 day * 500 = 500 gross, but two advances want 400 + 400
        $employee = cashAdvanceEmployee(['2026-08-03']);
        $older = CashAdvance::factory()->create([
            'employee_id' => $employee->id, 'date_granted' => '2026-07-01',
            'amount' => 150, 'deduction_per_payroll' => 400,
        ]);
        $newer = CashAdvance::factory()->create([
            'employee_id' => $employee->id, 'date_granted' => '2026-07-15',
            'amount' => 1000, 'deduction_per_payroll' => 400,
        ]);

        $payroll = generateWeeklyPayroll('2026-08-02', '2026-08-08');
        $item = PayrollItem::where('payroll_id', $payroll->id)->first();

        // older: capped at its 150 balance; newer: capped at the 350 net pay left
        expect($older->balance())->toBe(0.0)
            ->and($newer->balance())->toBe(650.0)
            ->and((float) $item->cash_advance_deduction)->toBe(500.0)
            ->and((float) $item->net_pay)->toBe(0.0);
    });

    it('restores the balance when the payroll is cancelled', function () {
        $employee = cashAdvanceEmployee(['2026-08-03', '2026-08-04']);
        $advance = CashAdvance::factory()->create([
            'employee_id' => $employee->id, 'date_granted' => '2026-07-20',
            'amount' => 2000, 'deduction_per_payroll' => 500,
        ]);

        $payroll = generateWeeklyPayroll('2026-08-02', '2026-08-08');
        expect($advance->balance())->toBe(1500.0);

        $payroll->update(['status' => PayrollStatus::Cancelled]);
        expect($advance->balance())->toBe(2000.0);
    });
});

describe('Cash advance resource', function () {
    it('lists advances and filters by outstanding / fully paid', function () {
        foreach (['ViewAny:CashAdvance', 'View:CashAdvance'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $this->admin->givePermissionTo(['ViewAny:CashAdvance', 'View:CashAdvance']);

        $open = CashAdvance::factory()->create(['amount' => 1000]);
        $settled = CashAdvance::factory()->create(['amount' => 500]);
        $settled->payments()->create(['payment_date' => now(), 'amount' => 500]);

        Livewire::test(ListCashAdvances::class)
            ->assertCanSeeTableRecords([$open, $settled])
            ->filterTable('status', 'outstanding')
            ->assertCanSeeTableRecords([$open])
            ->assertCanNotSeeTableRecords([$settled])
            ->filterTable('status', 'paid')
            ->assertCanSeeTableRecords([$settled])
            ->assertCanNotSeeTableRecords([$open]);

        Livewire::test(PaymentsRelationManager::class, [
            'ownerRecord' => $settled,
            'pageClass' => ViewCashAdvance::class,
        ])->assertCanSeeTableRecords($settled->payments);
    });
});

describe('Cash advance monitoring report', function () {
    it('builds beginning/period/ending balances for the summary and ledger', function () {
        $employee = Employee::factory()->create(['name' => 'Juan Dela Cruz']);

        $before = CashAdvance::factory()->create([
            'employee_id' => $employee->id, 'date_granted' => '2026-08-20', 'amount' => 2000,
        ]);
        $before->payments()->create(['payment_date' => '2026-08-25', 'amount' => 500]);

        $inPeriod = CashAdvance::factory()->create([
            'employee_id' => $employee->id, 'date_granted' => '2026-09-05', 'amount' => 1000,
            'purpose' => 'Medical',
        ]);
        $before->payments()->create(['payment_date' => '2026-09-15', 'amount' => 700, 'notes' => 'Cash']);
        $inPeriod->payments()->create(['payment_date' => '2026-09-30', 'amount' => 300]);

        // After the period — must be ignored.
        $inPeriod->payments()->create(['payment_date' => '2026-10-02', 'amount' => 100]);

        $result = (new CashAdvanceReportService)->build('2026-09-01', '2026-09-30');

        expect($result['summary'])->toHaveCount(1);
        expect($result['summary'][0])->toMatchArray([
            'employee' => 'Juan Dela Cruz',
            'beginning' => 1500.0,
            'advances' => 1000.0,
            'deductions' => 1000.0,
            'ending' => 1500.0,
        ]);

        $ledger = $result['ledgers'][0];
        expect($ledger['rows'])->toHaveCount(3)
            ->and(array_column($ledger['rows'], 'balance'))->toBe([2500.0, 1800.0, 1500.0])
            ->and($ledger['rows'][0]['particulars'])->toBe('Cash advance — Medical');
    });

    it('omits employees with no activity and no balance', function () {
        $settled = CashAdvance::factory()->create(['date_granted' => '2026-08-01', 'amount' => 500]);
        $settled->payments()->create(['payment_date' => '2026-08-15', 'amount' => 500]);

        $result = (new CashAdvanceReportService)->build('2026-09-01', '2026-09-30');

        expect($result['summary'])->toBeEmpty();
    });

    it('downloads CSV and PDF in both modes', function () {
        CashAdvance::factory()->create(['date_granted' => now()->subDay(), 'amount' => 1000]);

        foreach (['summary', 'ledger'] as $mode) {
            Livewire::test(CashAdvanceReport::class)
                ->set('mode', $mode)
                ->call('generate')
                ->callAction('exportCsv')
                ->assertFileDownloaded();

            Livewire::test(CashAdvanceReport::class)
                ->set('mode', $mode)
                ->call('generate')
                ->callAction('exportPdf')
                ->assertFileDownloaded();
        }
    });
});
