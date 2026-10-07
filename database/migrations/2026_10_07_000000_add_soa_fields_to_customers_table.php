<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Details of the latest Statement of Account billed to the customer,
            // shown on the AR Summary report. Filled in automatically when an SOA
            // is generated, and editable by hand from the report.
            $table->string('soa_number', 50)->nullable()->after('company');
            $table->date('soa_billing_date')->nullable()->after('soa_number');
            $table->string('soa_notes')->nullable()->after('soa_billing_date');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['soa_number', 'soa_billing_date', 'soa_notes']);
        });
    }
};
