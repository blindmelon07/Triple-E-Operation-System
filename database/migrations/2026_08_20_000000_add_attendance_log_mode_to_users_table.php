<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Per-employee: whether their ZKTeco punches are a simple
            // check-in/check-out pair (two) or also include a lunch
            // break-out/break-in pair (four). See App\Enums\AttendanceLogMode.
            $table->string('attendance_log_mode', 10)->default('two')->after('biometric_pin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('attendance_log_mode');
        });
    }
};
