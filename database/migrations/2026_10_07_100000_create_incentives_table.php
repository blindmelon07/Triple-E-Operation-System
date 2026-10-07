<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sales incentives entered by hand. Each one is paid either through
        // payroll (payroll_item_id set when a payroll picks it up) or released
        // separately (released_at set when handed over).
        Schema::create('incentives', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->string('payout_method', 20)->default('payroll');
            $table->foreignId('payroll_item_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'payout_method']);
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->decimal('incentive', 12, 2)->default(0)->after('bonus_description');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn('incentive');
        });

        Schema::dropIfExists('incentives');
    }
};
