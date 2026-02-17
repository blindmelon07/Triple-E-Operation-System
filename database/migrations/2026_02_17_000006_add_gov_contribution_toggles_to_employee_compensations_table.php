<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_compensations', function (Blueprint $table) {
            $table->boolean('sss_enabled')->default(true)->after('days_off');
            $table->boolean('philhealth_enabled')->default(true)->after('sss_enabled');
            $table->boolean('pagibig_enabled')->default(true)->after('philhealth_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('employee_compensations', function (Blueprint $table) {
            $table->dropColumn(['sss_enabled', 'philhealth_enabled', 'pagibig_enabled']);
        });
    }
};
