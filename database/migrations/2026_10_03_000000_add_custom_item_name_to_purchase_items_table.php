<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow purchase lines that aren't in the product catalog (one-off
     * parts, services, fees). Such lines have no product_id and never
     * touch inventory.
     */
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->string('custom_item_name')->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('custom_item_name');
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};
