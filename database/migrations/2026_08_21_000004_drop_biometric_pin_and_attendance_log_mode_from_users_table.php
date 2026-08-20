<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These columns now live exclusively on employees (see
     * 2026_08_21_000000_create_employees_table.php + the backfill
     * migration that copied every existing value across).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The unique index on biometric_pin must go before the column
            // itself — SQLite (used by the test suite) refuses to drop a
            // column a unique index still references.
            $table->dropUnique(['biometric_pin']);
            $table->dropColumn(['biometric_pin', 'attendance_log_mode']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('biometric_pin')->nullable()->unique()->after('email');
            $table->string('attendance_log_mode', 10)->default('two')->after('biometric_pin');
        });
    }
};
