<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->after('total');
            $table->decimal('amount_paid', 12, 2)->default(0)->after('payment_status');
            $table->date('due_date')->nullable()->after('amount_paid');
            $table->date('paid_date')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'amount_paid', 'due_date', 'paid_date']);
        });
    }
};
