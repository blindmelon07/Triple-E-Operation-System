<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The cashier's count of each bill/coin when closing the register,
     * e.g. {"1000": 0, "500": 2, "0.25": 4}. closing_amount is its total.
     */
    public function up(): void
    {
        Schema::table('cash_register_sessions', function (Blueprint $table) {
            $table->json('cash_breakdown')->nullable()->after('closing_amount');
        });
    }

    public function down(): void
    {
        Schema::table('cash_register_sessions', function (Blueprint $table) {
            $table->dropColumn('cash_breakdown');
        });
    }
};
