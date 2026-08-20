<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run only after confirming (see 2026_08_21_000002's backfill) that
     * every row with a user_id got a matching employee_id — this drop is
     * destructive and rollback here is structural-only, not a data restore.
     */
    public function up(): void
    {
        // NOTE: on the dev DB this migration was first run, the attendances
        // block below failed partway (MySQL error 1830 — NOT NULL vs an
        // existing SET NULL foreign key) and was finished manually via
        // tinker before this file was corrected. The block is left intact
        // for any other environment running this migration fresh.
        Schema::table('attendances', function (Blueprint $table) {
            // The FK constraint on user_id is backed by the composite unique
            // index below, so the constraint must be dropped first — MySQL
            // refuses to drop an index a foreign key still relies on.
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'date']);
            $table->dropColumn('user_id');

            // employee_id's FK was created nullOnDelete, which MySQL won't
            // allow alongside NOT NULL — drop and recreate it cascadeOnDelete
            // (matching the old user_id column's delete rule) around the
            // NOT NULL tightening.
            $table->dropForeign(['employee_id']);
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable(false)->change();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->unique(['employee_id', 'date']);
        });

        Schema::table('employee_compensations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
            $table->dropForeign(['employee_id']);
        });
        Schema::table('employee_compensations', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable(false)->change();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->unique(['employee_id']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropForeign(['employee_id']);
        });
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable(false)->change();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropForeign(['employee_id']);
        });
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable(false)->change();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('zk_attendance_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            // employee_id stays nullable here, mirroring the old nullable
            // user_id — punches from an unmapped PIN are still logged.
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'date']);
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('employee_compensations', function (Blueprint $table) {
            $table->dropUnique(['employee_id']);
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('zk_attendance_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }
};
