<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_advances', function (Blueprint $table) {
            // regular: capped at one cut-off's salary, deducted in full next payroll.
            // emergency: repaid in equal installments over up to 2 months.
            $table->string('type', 20)->default('regular')->after('reference_number');
        });
    }

    public function down(): void
    {
        Schema::table('cash_advances', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
