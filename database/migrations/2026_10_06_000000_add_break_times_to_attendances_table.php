<?php

use App\Services\ZkAttendanceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->time('break_out')->nullable()->after('time_in');
            $table->time('break_in')->nullable()->after('break_out');
        });

        // The store runs a fixed 4-log day (in, break out, break in, out)
        // for everyone, so make that the default and switch existing staff.
        Schema::table('employees', function (Blueprint $table) {
            $table->string('attendance_log_mode', 10)->default('four')->change();
        });
        DB::table('employees')->update(['attendance_log_mode' => 'four']);

        // Re-derive device-sourced days with the new punch-order rules so
        // break times and worked hours are filled in for existing records.
        app(ZkAttendanceService::class)->recalculateBiometricAttendance();
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('attendance_log_mode', 10)->default('two')->change();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['break_out', 'break_in']);
        });
    }
};
