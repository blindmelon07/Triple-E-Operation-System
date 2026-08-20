<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables whose user_id actually means "the employee this record is
     * about" (as opposed to an actor/approver column like recorded_by,
     * approved_by, generated_by, which stay pointed at users).
     */
    protected array $tables = [
        'attendances',
        'employee_compensations',
        'leave_requests',
        'payroll_items',
        'zk_attendance_logs',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                // Nullable everywhere for now (even on tables where user_id
                // is required) so this migration can run against existing
                // rows; tightened to NOT NULL once backfilled and verified
                // in the next migration.
                $blueprint->foreignId('employee_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            });

            // Backfill per-employee rather than a single multi-table
            // UPDATE ... JOIN — that syntax is MySQL-only and SQLite (used
            // by the test suite) can't parse it, which took down every
            // feature test that touches these tables.
            DB::table('employees')
                ->select('id', 'user_id')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->chunkById(200, function ($employees) use ($table) {
                    foreach ($employees as $employee) {
                        DB::table($table)
                            ->where('user_id', $employee->user_id)
                            ->update(['employee_id' => $employee->id]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('employee_id');
            });
        }
    }
};
