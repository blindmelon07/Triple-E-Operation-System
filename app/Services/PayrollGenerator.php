<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\CashAdvance;
use App\Models\CashAdvancePayment;
use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\GovernmentContribution;
use App\Models\Incentive;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Builds a payroll's items from attendance, leave, compensation, incentives
 * and cash advances. Used when a payroll is created and when a draft is
 * regenerated (period changed, or attendance fixed after the fact).
 */
class PayrollGenerator
{
    /**
     * Generate items for a payroll that has none yet.
     *
     * @return array{employees: int, total_net: float, missing_compensation: Collection<int, string>}
     */
    public function generate(Payroll $payroll): array
    {
        return DB::transaction(fn () => $this->build($payroll));
    }

    /**
     * Throw away a draft payroll's items and build them again. Deleting an
     * item cascades to its cash advance payments and unlinks its incentives,
     * so both go back to "outstanding" before being picked up again.
     * Manual edits on the items (overtime, bonus, other deductions) are lost.
     *
     * @return array{employees: int, total_net: float, missing_compensation: Collection<int, string>}
     */
    public function regenerate(Payroll $payroll): array
    {
        return DB::transaction(function () use ($payroll) {
            $payroll->payrollItems()->delete();

            return $this->build($payroll);
        });
    }

    /**
     * Active employees who have attendance in the payroll period but no
     * compensation record at all, so payroll skips them.
     *
     * @return Collection<int, string>
     */
    public function employeesMissingCompensation(Payroll $payroll): Collection
    {
        return Employee::query()
            ->where('is_active', true)
            ->whereDoesntHave('employeeCompensation')
            ->whereHas('attendances', fn ($q) => $q->whereBetween('date', [
                $payroll->pay_period_start->toDateString(),
                $payroll->pay_period_end->toDateString(),
            ]))
            ->orderBy('name')
            ->pluck('name');
    }

    /**
     * @return array{employees: int, total_net: float, missing_compensation: Collection<int, string>}
     */
    private function build(Payroll $payroll): array
    {
        $periodStart = Carbon::parse($payroll->pay_period_start)->startOfDay();
        $periodEnd = Carbon::parse($payroll->pay_period_end)->startOfDay();

        // Get all employees with matching pay period type
        $compensations = EmployeeCompensation::where('pay_period', $payroll->pay_period_type)
            ->with('employee')
            ->get();

        $totalGross = 0;
        $totalDeductions = 0;
        $totalNet = 0;

        foreach ($compensations as $comp) {
            $employeeId = $comp->employee_id;
            $daysOff = $comp->days_off ?? [];

            // Working days in the period, excluding the employee's days off
            $workingDates = collect($this->datesBetween($periodStart, $periodEnd))
                ->reject(fn (string $date) => in_array(strtolower(Carbon::parse($date)->format('l')), $daysOff))
                ->values();

            // Attendance records on working days, keyed by date. toBase()
            // matters: an Eloquent collection's only() filters by model id,
            // not by array key.
            $attendances = Attendance::where('employee_id', $employeeId)
                ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->get()
                ->toBase()
                ->keyBy(fn ($attendance) => Carbon::parse($attendance->date)->toDateString())
                ->only($workingDates->all());

            $workedStatuses = [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::HalfDay];

            // Count days worked (present or late), half days as 0.5
            $daysWorked = $attendances->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late])->count();
            $daysWorked += $attendances->where('status', AttendanceStatus::HalfDay)->count() * 0.5;

            // Approved leave days that fall inside the period, on working
            // days. Leave that starts before or ends after the cut-off only
            // counts the part inside it.
            $leaveDates = ['paid' => [], 'unpaid' => []];
            LeaveRequest::where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $periodEnd)
                ->whereDate('end_date', '>=', $periodStart)
                ->with('leaveType')
                ->get()
                ->each(function (LeaveRequest $leave) use (&$leaveDates, $periodStart, $periodEnd, $workingDates) {
                    $from = $leave->start_date->copy()->max($periodStart);
                    $to = $leave->end_date->copy()->min($periodEnd);
                    $bucket = $leave->leaveType?->is_paid ? 'paid' : 'unpaid';

                    foreach ($this->datesBetween($from, $to) as $date) {
                        if ($workingDates->contains($date)) {
                            $leaveDates[$bucket][] = $date;
                        }
                    }
                });

            // Paid leave counts as a worked day, unless they also clocked in
            $workedDates = $attendances->whereIn('status', $workedStatuses)->keys();
            $paidLeaveDays = collect($leaveDates['paid'])->unique()->diff($workedDates)->count();
            $daysWorked += $paidLeaveDays;

            // Absences: days marked absent, plus past working days with no
            // attendance record and no approved leave. Days after today
            // haven't happened yet, so they're never counted as absent.
            $today = today()->toDateString();
            $unrecordedDays = $workingDates
                ->filter(fn (string $date) => $date <= $today)
                ->diff($attendances->keys())
                ->diff($leaveDates['paid'])
                ->diff($leaveDates['unpaid'])
                ->count();
            $daysAbsent = $attendances->where('status', AttendanceStatus::Absent)->count() + $unrecordedDays;

            // Count late records and calculate late minutes
            $lateRecords = $attendances->where('status', AttendanceStatus::Late);
            $lateCount = $lateRecords->count();
            $lateMinutes = 0;

            foreach ($lateRecords as $record) {
                if ($record->time_in) {
                    $timeIn = Carbon::parse($record->time_in);
                    $threshold = Carbon::parse('09:00:00');
                    if ($timeIn->greaterThan($threshold)) {
                        // Carbon 3 defaults diffInMinutes() to a signed
                        // result (Carbon 2 was always absolute) — without
                        // the explicit `true` here this comes back negative
                        // whenever $timeIn is after $threshold, turning the
                        // late deduction into a bonus.
                        $lateMinutes += $timeIn->diffInMinutes($threshold, true);
                    }
                }
            }

            // Calculate gross pay
            $basePay = (float) $comp->daily_rate * $daysWorked;
            $allowance = (float) $comp->allowance;

            // Sales incentives set to "Add to payroll", dated on or before the
            // end of this cut-off and not already in a live payroll.
            $incentives = Incentive::awaitingPayroll()
                ->where('employee_id', $employeeId)
                ->whereDate('date', '<=', $periodEnd)
                ->get();
            $incentiveTotal = round((float) $incentives->sum('amount'), 2);

            $grossPay = $basePay + $allowance + $incentiveTotal;

            // Calculate late deduction
            $lateDeduction = 0;
            if ($comp->late_deduction_type === 'per_minute') {
                // daily_rate / 480 minutes (8 hrs) per minute
                $ratePerMinute = (float) $comp->daily_rate / 480;
                $lateDeduction = round($ratePerMinute * $lateMinutes, 2);
            } else {
                // Fixed amount per late occurrence
                $lateDeduction = round((float) $comp->late_deduction_amount * $lateCount, 2);
            }

            // Calculate government deductions based on monthly equivalent (only if enabled per employee)
            $monthlyEquivalent = $comp->getMonthlyEquivalent();

            $sssDeduction = 0;
            if ($comp->sss_enabled) {
                $sssDeduction = GovernmentContribution::getSssDeduction($monthlyEquivalent);
            }

            $philhealthDeduction = 0;
            if ($comp->philhealth_enabled) {
                $philhealthDeduction = GovernmentContribution::getPhilhealthDeduction($monthlyEquivalent);
                if ($monthlyEquivalent > 10000) {
                    $philhealthDeduction = min(round($monthlyEquivalent * 0.025, 2), 2500);
                }
            }

            $pagibigDeduction = 0;
            if ($comp->pagibig_enabled) {
                $pagibigDeduction = GovernmentContribution::getPagibigDeduction($monthlyEquivalent);
            }

            // For semi-monthly, gov deductions are split in half (deducted per cutoff)
            if ($payroll->pay_period_type->value === 'semi_monthly') {
                $sssDeduction = round($sssDeduction / 2, 2);
                $philhealthDeduction = round($philhealthDeduction / 2, 2);
                $pagibigDeduction = round($pagibigDeduction / 2, 2);
            }

            // For weekly, gov deductions spread across ~4 weeks
            if ($payroll->pay_period_type->value === 'weekly') {
                $sssDeduction = round($sssDeduction / 4, 2);
                $philhealthDeduction = round($philhealthDeduction / 4, 2);
                $pagibigDeduction = round($pagibigDeduction / 4, 2);
            }

            $totalItemDeductions = $lateDeduction + $sssDeduction + $philhealthDeduction + $pagibigDeduction;

            // Cash advance installments, oldest advance first. Each advance
            // contributes min(its per-payroll installment, its balance), and
            // the combined deduction is capped at what's left of net pay so a
            // short cutoff never produces a negative payslip — whatever
            // isn't deducted simply stays on the balance for next payroll.
            $availableForCa = max(0, round($grossPay - $totalItemDeductions, 2));
            $caDeductions = [];
            foreach (CashAdvance::outstandingFor($employeeId) as $advance) {
                if ($availableForCa <= 0) {
                    break;
                }

                $balance = round((float) $advance->amount - (float) $advance->paid_amount, 2);
                $installment = min((float) $advance->deduction_per_payroll, $balance, $availableForCa);

                if ($installment > 0) {
                    $caDeductions[$advance->id] = $installment;
                    $availableForCa = round($availableForCa - $installment, 2);
                }
            }
            $cashAdvanceDeduction = round(array_sum($caDeductions), 2);

            $totalItemDeductions += $cashAdvanceDeduction;
            $netPay = $grossPay - $totalItemDeductions;

            $item = PayrollItem::create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employeeId,
                'daily_rate' => $comp->daily_rate,
                'days_worked' => $daysWorked,
                'days_absent' => $daysAbsent,
                'overtime_hours' => 0,
                'overtime_pay' => 0,
                'bonus' => 0,
                'allowance' => $allowance,
                'incentive' => $incentiveTotal,
                'gross_pay' => $grossPay,
                'late_count' => $lateCount,
                'late_minutes' => $lateMinutes,
                'late_deduction' => $lateDeduction,
                'sss_deduction' => $sssDeduction,
                'philhealth_deduction' => $philhealthDeduction,
                'pagibig_deduction' => $pagibigDeduction,
                'cash_advance_deduction' => $cashAdvanceDeduction,
                'other_deduction' => 0,
                'total_deductions' => $totalItemDeductions,
                'net_pay' => $netPay,
            ]);

            if ($incentives->isNotEmpty()) {
                Incentive::whereKey($incentives->modelKeys())->update(['payroll_item_id' => $item->id]);
            }

            foreach ($caDeductions as $cashAdvanceId => $amount) {
                CashAdvancePayment::create([
                    'cash_advance_id' => $cashAdvanceId,
                    'payroll_item_id' => $item->id,
                    'user_id' => Auth::id(),
                    'payment_date' => $periodEnd->toDateString(),
                    'amount' => $amount,
                    'notes' => "Payroll {$payroll->payroll_number}",
                ]);
            }

            $totalGross += $grossPay;
            $totalDeductions += $totalItemDeductions;
            $totalNet += $netPay;
        }

        // Update payroll totals
        $payroll->update([
            'total_gross' => $totalGross,
            'total_deductions' => $totalDeductions,
            'total_net' => $totalNet,
        ]);

        return [
            'employees' => $compensations->count(),
            'total_net' => $totalNet,
            'missing_compensation' => $this->employeesMissingCompensation($payroll),
        ];
    }

    /**
     * Every date from $from to $to inclusive, as Y-m-d strings. Works on an
     * immutable copy so the caller's dates are never moved.
     *
     * @return array<int, string>
     */
    private function datesBetween(CarbonInterface $from, CarbonInterface $to): array
    {
        $dates = [];

        for ($date = $from->toImmutable()->startOfDay(); $date->lte($to); $date = $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        return $dates;
    }

    /**
     * Tell the user how a generation run went. Stays on screen when
     * employees were skipped so the names can actually be read.
     *
     * @param  array{employees: int, total_net: float, missing_compensation: Collection<int, string>}  $result
     */
    public static function notify(string $title, array $result): void
    {
        $notification = Notification::make()
            ->title($title)
            ->body(static::summary($result));

        if ($result['missing_compensation']->isNotEmpty()) {
            $notification->warning()->persistent();
        } else {
            $notification->success();
        }

        $notification->send();
    }

    /**
     * Notification body summarising a generation run.
     *
     * @param  array{employees: int, total_net: float, missing_compensation: Collection<int, string>}  $result
     */
    public static function summary(array $result): string
    {
        $body = "Generated payroll for {$result['employees']} employees. Total net: ₱".number_format($result['total_net'], 2);

        if ($result['missing_compensation']->isNotEmpty()) {
            $body .= "\n\nSkipped — attendance but no compensation set up: "
                .$result['missing_compensation']->implode(', ')
                .'. Add their compensation, then regenerate this payroll.';
        }

        return $body;
    }
}
