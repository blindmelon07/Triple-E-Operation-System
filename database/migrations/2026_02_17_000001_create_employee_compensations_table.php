<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('daily_rate', 12, 2);
            $table->enum('pay_period', ['daily', 'weekly', 'semi_monthly'])->default('semi_monthly');
            $table->decimal('overtime_rate_multiplier', 3, 2)->default(1.25);
            $table->enum('late_deduction_type', ['per_minute', 'fixed'])->default('per_minute');
            $table->decimal('late_deduction_amount', 12, 2)->default(0);
            $table->decimal('allowance', 12, 2)->default(0);
            $table->text('allowance_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_compensations');
    }
};
