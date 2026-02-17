<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Enums\AttendanceStatus;
use App\Filament\Resources\Payrolls\PayrollResource;
use App\Models\Attendance;
use App\Models\EmployeeCompensation;
use App\Models\GovernmentContribution;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['payroll_number'] = Payroll::generatePayrollNumber();
        $data['status'] = 'draft';
        $data['generated_by'] = Auth::id();
        $data['total_gross'] = 0;
        $data['total_deductions'] = 0;
        $data['total_net'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $payroll = $this->record;
        $periodStart = Carbon::parse($payroll->pay_period_start);
        $periodEnd = Carbon::parse($payroll->pay_period_end);

        // Get all employees with matching pay period type
        $compensations = EmployeeCompensation::where('pay_period', $payroll->pay_period_type)
            ->with('user')
            ->get();

        if ($compensations->isEmpty()) {
            Notification::make()
                ->title('No Employees Found')
                ->body('No employees found with the selected pay period type.')
                ->warning()
                ->send();

            return;
        }

        $totalGross = 0;
        $totalDeductions = 0;
        $totalNet = 0;

        foreach ($compensations as $comp) {
            $userId = $comp->user_id;
            $daysOff = $comp->days_off ?? [];

            // Calculate working days in the period (excluding employee's days off)
            $workingDaysInPeriod = 0;
            $currentDate = $periodStart->copy();
            while ($currentDate->lte($periodEnd)) {
                $dayName = strtolower($currentDate->format('l'));
                if (! in_array($dayName, $daysOff)) {
                    $workingDaysInPeriod++;
                }
                $currentDate->addDay();
            }

            // Query attendance records in the period (exclude days off)
            $attendances = Attendance::where('user_id', $userId)
                ->whereBetween('date', [$periodStart, $periodEnd])
                ->get()
                ->filter(function ($attendance) use ($daysOff) {
                    $dayName = strtolower(Carbon::parse($attendance->date)->format('l'));
                    return ! in_array($dayName, $daysOff);
                });

            // Count days worked (present or late)
            $daysWorked = $attendances->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late])->count();

            // Count half days as 0.5
            $halfDays = $attendances->where('status', AttendanceStatus::HalfDay)->count();
            $daysWorked += $halfDays * 0.5;

            // Count approved paid leaves as worked days
            $paidLeaveDays = LeaveRequest::where('user_id', $userId)
                ->where('status', 'approved')
                ->whereHas('leaveType', fn ($q) => $q->where('is_paid', true))
                ->where(function ($q) use ($periodStart, $periodEnd) {
                    $q->whereBetween('start_date', [$periodStart, $periodEnd])
                        ->orWhereBetween('end_date', [$periodStart, $periodEnd]);
                })
                ->sum('total_days');

            $daysWorked += (float) $paidLeaveDays;

            // Count absences (only on working days, excluding days off)
            $daysAbsent = $attendances->where('status', AttendanceStatus::Absent)->count();

            // Count late records and calculate late minutes
            $lateRecords = $attendances->where('status', AttendanceStatus::Late);
            $lateCount = $lateRecords->count();
            $lateMinutes = 0;

            foreach ($lateRecords as $record) {
                if ($record->time_in) {
                    $timeIn = Carbon::parse($record->time_in);
                    $threshold = Carbon::parse('09:00:00');
                    if ($timeIn->greaterThan($threshold)) {
                        $lateMinutes += $timeIn->diffInMinutes($threshold);
                    }
                }
            }

            // Calculate gross pay
            $basePay = (float) $comp->daily_rate * $daysWorked;
            $allowance = (float) $comp->allowance;
            $grossPay = $basePay + $allowance;

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
            $netPay = $grossPay - $totalItemDeductions;

            PayrollItem::create([
                'payroll_id' => $payroll->id,
                'user_id' => $userId,
                'daily_rate' => $comp->daily_rate,
                'days_worked' => $daysWorked,
                'days_absent' => $daysAbsent,
                'overtime_hours' => 0,
                'overtime_pay' => 0,
                'bonus' => 0,
                'allowance' => $allowance,
                'gross_pay' => $grossPay,
                'late_count' => $lateCount,
                'late_minutes' => $lateMinutes,
                'late_deduction' => $lateDeduction,
                'sss_deduction' => $sssDeduction,
                'philhealth_deduction' => $philhealthDeduction,
                'pagibig_deduction' => $pagibigDeduction,
                'other_deduction' => 0,
                'total_deductions' => $totalItemDeductions,
                'net_pay' => $netPay,
            ]);

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

        Notification::make()
            ->title('Payroll Generated!')
            ->body("Generated payroll for {$compensations->count()} employees. Total net: ₱" . number_format($totalNet, 2))
            ->success()
            ->send();
    }
}
