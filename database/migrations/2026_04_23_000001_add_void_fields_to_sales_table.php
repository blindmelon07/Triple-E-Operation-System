<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_voided')->default(false)->after('paid_date');
            $table->timestamp('voided_at')->nullable()->after('is_voided');
            $table->string('void_reason')->nullable()->after('voided_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['is_voided', 'voided_at', 'void_reason']);
        });
    }
};
