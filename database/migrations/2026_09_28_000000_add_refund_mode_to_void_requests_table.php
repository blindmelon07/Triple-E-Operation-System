<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('void_requests', function (Blueprint $table) {
            // How an approved item void/exchange handed back money the customer had
            // already paid: 'cash' out of the drawer, or 'credit' applied against the
            // customer's other unpaid invoices. Null until approved.
            $table->string('refund_mode')->nullable()->after('rejection_reason');
            $table->decimal('credited_amount', 12, 2)->default(0)->after('refund_mode');
        });
    }

    public function down(): void
    {
        Schema::table('void_requests', function (Blueprint $table) {
            $table->dropColumn(['refund_mode', 'credited_amount']);
        });
    }
};
