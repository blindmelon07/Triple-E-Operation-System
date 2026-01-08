<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'payment_term_days')) {
                $table->integer('payment_term_days')->default(0)->after('name');
            }
            if (! Schema::hasColumn('customers', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('payment_term_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['payment_term_days', 'contact_person']);
        });
    }
};
