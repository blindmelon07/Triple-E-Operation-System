<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Schedule: 8:00 AM - 5:00 PM (8 hrs work + 1 hr lunch break 12nn-1pm)
     * Late threshold: after 8:00 AM (not 9:00 AM)
     */
    public function run(): void
    {
        // Find employees linked to a cashier User account
        $cashiers = Employee::whereHas('user.roles', fn ($q) => $q->where('name', 'like', '%cashier%'))->get();

        if ($cashiers->isEmpty()) {
            $this->command->warn('No cashier employees found. Listing all employees:');
            Employee::with('user.roles')->get()->each(function ($employee) {
                $roles = $employee->user?->roles->pluck('name')->join(', ') ?? '(no login)';
                $this->command->info("  ID: {$employee->id} | {$employee->name} | Roles: {$roles}");
            });

            return;
        }

        foreach ($cashiers as $cashier) {
            // Delete existing January 2026 records first
            Attendance::where('employee_id', $cashier->id)
                ->whereYear('date', 2026)
                ->whereMonth('date', 1)
                ->delete();

            $this->command->info("Cleared old records for {$cashier->name}. Generating fresh attendance...");
            $this->generateMonthAttendance($cashier->id, 2026, 1);
            $this->command->info("  Generated attendance for {$cashier->name} (ID: {$cashier->id})");
        }
    }

    private function generateMonthAttendance(int $employeeId, int $year, int $month): void
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // Skip Sundays (day off)
            if ($currentDate->isSunday()) {
                $currentDate->addDay();

                continue;
            }

            $rand = rand(1, 100);

            if ($rand <= 75) {
                // 75% - Present: arrives 7:30-7:59 AM, leaves at 5:00 PM (+/- 10 min)
                $timeIn = Carbon::parse('07:30:00')->addMinutes(rand(0, 29));
                $timeOut = Carbon::parse('17:00:00')->addMinutes(rand(-10, 15));
                $totalHours = $this->calculateWorkHours($timeIn, $timeOut);
                $status = 'present';
            } elseif ($rand <= 90) {
                // 15% - Late: arrives 8:05-8:45 AM, leaves at 5:00 PM or later
                $timeIn = Carbon::parse('08:05:00')->addMinutes(rand(0, 40));
                $timeOut = Carbon::parse('17:00:00')->addMinutes(rand(0, 30));
                $totalHours = $this->calculateWorkHours($timeIn, $timeOut);
                $status = 'late';
            } elseif ($rand <= 95) {
                // 5% - Half day: arrives 8:00 AM, leaves at 12:00 PM (before lunch)
                $timeIn = Carbon::parse('08:00:00')->addMinutes(rand(0, 15));
                $timeOut = Carbon::parse('12:00:00')->addMinutes(rand(0, 10));
                // No lunch break deduction for half day (left before lunch)
                $totalHours = round(abs($timeOut->diffInMinutes($timeIn)) / 60, 2);
                $status = 'half_day';
            } else {
                // 5% - Absent
                Attendance::create([
                    'employee_id' => $employeeId,
                    'date' => $currentDate->format('Y-m-d'),
                    'time_in' => null,
                    'time_out' => null,
                    'total_hours' => null,
                    'status' => 'absent',
                ]);
                $currentDate->addDay();

                continue;
            }

            Attendance::create([
                'employee_id' => $employeeId,
                'date' => $currentDate->format('Y-m-d'),
                'time_in' => $timeIn->format('H:i:s'),
                'time_out' => $timeOut->format('H:i:s'),
                'total_hours' => $totalHours,
                'status' => $status,
            ]);

            $currentDate->addDay();
        }
    }

    /**
     * Calculate work hours with 1-hour lunch break (12:00-1:00 PM) deducted.
     * Schedule: 8AM-5PM = 9 hours - 1 hour lunch = 8 hours work.
     */
    private function calculateWorkHours(Carbon $timeIn, Carbon $timeOut): float
    {
        $totalMinutes = abs($timeOut->diffInMinutes($timeIn));

        // Deduct 1 hour lunch break if employee worked through the lunch period
        $lunchStart = Carbon::parse('12:00:00');
        $lunchEnd = Carbon::parse('13:00:00');

        if ($timeIn->lt($lunchEnd) && $timeOut->gt($lunchStart)) {
            // Calculate overlap with lunch break
            $overlapStart = $timeIn->gt($lunchStart) ? $timeIn : $lunchStart;
            $overlapEnd = $timeOut->lt($lunchEnd) ? $timeOut : $lunchEnd;
            $lunchMinutes = abs($overlapEnd->diffInMinutes($overlapStart));
            $totalMinutes -= $lunchMinutes;
        }

        return round($totalMinutes / 60, 2);
    }
}
