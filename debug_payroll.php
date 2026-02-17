<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Attendance Records ===\n";
$all = App\Models\Attendance::orderBy('date')->get();
echo "Total: {$all->count()}\n";
if ($all->count() > 0) {
    echo "Date range: {$all->first()->date} to {$all->last()->date}\n";
    echo "\nBy user:\n";
    $all->groupBy('user_id')->each(function ($records, $userId) {
        $user = App\Models\User::find($userId);
        echo "  User {$userId} ({$user->name}): {$records->count()} records | {$records->first()->date} to {$records->last()->date}\n";
    });
}

echo "\n=== Employee Compensations ===\n";
App\Models\EmployeeCompensation::all()->each(function ($c) {
    $user = App\Models\User::find($c->user_id);
    echo "  User {$c->user_id} ({$user->name}) | Rate: {$c->daily_rate} | Period: {$c->pay_period->value} | Days Off: " . json_encode($c->days_off) . "\n";
    echo "    SSS: " . ($c->sss_enabled ? 'Y' : 'N') . " | PhilHealth: " . ($c->philhealth_enabled ? 'Y' : 'N') . " | PagIBIG: " . ($c->pagibig_enabled ? 'Y' : 'N') . "\n";
});

echo "\n=== Payrolls ===\n";
$payrolls = App\Models\Payroll::all();
if ($payrolls->isEmpty()) {
    echo "No payrolls found.\n";
} else {
    foreach ($payrolls as $p) {
        echo "  #{$p->payroll_number} | {$p->pay_period_start} to {$p->pay_period_end} | Type: {$p->pay_period_type->value} | Status: {$p->status->value}\n";
        echo "    Gross: {$p->total_gross} | Deductions: {$p->total_deductions} | Net: {$p->total_net}\n";

        $items = App\Models\PayrollItem::where('payroll_id', $p->id)->get();
        foreach ($items as $item) {
            $user = App\Models\User::find($item->user_id);
            echo "    Item: {$user->name} | Days: {$item->days_worked} | Gross: {$item->gross_pay} | Net: {$item->net_pay}\n";
        }
    }
}

echo "\n=== Simulating Payroll Calc for Latest Payroll ===\n";
$payroll = App\Models\Payroll::latest()->first();
if (!$payroll) {
    echo "No payroll to simulate.\n";
    exit;
}

$periodStart = Carbon\Carbon::parse($payroll->pay_period_start);
$periodEnd = Carbon\Carbon::parse($payroll->pay_period_end);

$compensations = App\Models\EmployeeCompensation::where('pay_period', $payroll->pay_period_type)
    ->with('user')
    ->get();

echo "Pay period type: {$payroll->pay_period_type->value}\n";
echo "Period: {$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')}\n";
echo "Matching compensations: {$compensations->count()}\n";

foreach ($compensations as $comp) {
    echo "\n  Employee: {$comp->user->name} (ID: {$comp->user_id})\n";
    echo "  Days off: " . json_encode($comp->days_off) . "\n";

    $attendances = App\Models\Attendance::where('user_id', $comp->user_id)
        ->whereBetween('date', [$periodStart, $periodEnd])
        ->get();

    echo "  Raw attendance in period: {$attendances->count()}\n";

    $daysOff = $comp->days_off ?? [];
    $filtered = $attendances->filter(function ($attendance) use ($daysOff) {
        $dayName = strtolower(Carbon\Carbon::parse($attendance->date)->format('l'));
        return !in_array($dayName, $daysOff);
    });

    echo "  After filtering days off: {$filtered->count()}\n";

    $present = $filtered->where('status', App\Enums\AttendanceStatus::Present)->count();
    $late = $filtered->where('status', App\Enums\AttendanceStatus::Late)->count();
    $halfDay = $filtered->where('status', App\Enums\AttendanceStatus::HalfDay)->count();
    $absent = $filtered->where('status', App\Enums\AttendanceStatus::Absent)->count();

    echo "  Present: {$present} | Late: {$late} | Half Day: {$halfDay} | Absent: {$absent}\n";

    // Check what the status values actually are
    if ($attendances->count() > 0) {
        echo "  First 3 attendance records:\n";
        foreach ($attendances->take(3) as $a) {
            echo "    Date: {$a->date} | Status: " . (is_object($a->status) ? $a->status->value : $a->status) . " | Type: " . gettype($a->status) . "\n";
        }
    }
}
