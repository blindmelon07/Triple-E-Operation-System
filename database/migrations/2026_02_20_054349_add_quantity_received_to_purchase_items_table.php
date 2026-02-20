<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->integer('quantity_received')->default(0)->after('quantity');
        });

        // Existing records already updated inventory — set received = ordered
        DB::table('purchase_items')->update(['quantity_received' => DB::raw('quantity')]);
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('quantity_received');
        });
    }
};
