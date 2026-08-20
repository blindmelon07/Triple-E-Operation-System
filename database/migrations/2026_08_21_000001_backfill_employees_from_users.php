<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One-time data backfill: create an Employee row for every existing
     * User, preserving the link via employees.user_id so nothing currently
     * working (attendance, payroll, biometric punches) loses its owner once
     * the dependent tables are repointed at employee_id in the next migration.
     */
    public function up(): void
    {
        $now = now();

        $users = DB::table('users')
            ->select('id', 'name', 'email', 'biometric_pin', 'attendance_log_mode')
            ->get();

        foreach ($users as $user) {
            DB::table('employees')->insert([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'biometric_pin' => $user->biometric_pin,
                'attendance_log_mode' => $user->attendance_log_mode ?? 'two',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('employees')->whereNotNull('user_id')->delete();
    }
};
