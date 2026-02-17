<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('daily_rate', 12, 2);
            $table->decimal('days_worked', 5, 2);
            $table->decimal('days_absent', 5, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->text('bonus_description')->nullable();
            $table->decimal('allowance', 12, 2)->default(0);
            $table->decimal('gross_pay', 12, 2);
            $table->integer('late_count')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->decimal('late_deduction', 12, 2)->default(0);
            $table->decimal('sss_deduction', 12, 2)->default(0);
            $table->decimal('philhealth_deduction', 12, 2)->default(0);
            $table->decimal('pagibig_deduction', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);
            $table->text('other_deduction_description')->nullable();
            $table->decimal('total_deductions', 12, 2);
            $table->decimal('net_pay', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
