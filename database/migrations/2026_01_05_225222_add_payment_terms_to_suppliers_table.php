<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Only add payment_term_days - other columns already exist
            if (! Schema::hasColumn('suppliers', 'payment_term_days')) {
                $table->integer('payment_term_days')->default(0)->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['payment_term_days']);
        });
    }
};
