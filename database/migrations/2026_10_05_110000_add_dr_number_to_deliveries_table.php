<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            // Number from the client's own pre-printed DR booklet, typed in by
            // hand. Nullable because older deliveries never had one recorded.
            $table->string('dr_number', 50)->nullable()->index()->after('sale_id');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn('dr_number');
        });
    }
};
