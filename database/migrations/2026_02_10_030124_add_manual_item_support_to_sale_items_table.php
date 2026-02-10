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
        Schema::table('sale_items', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['product_id']);

            // Make product_id nullable and add new columns
            $table->foreignId('product_id')->nullable()->change();
            $table->string('product_description')->nullable()->after('product_id');
            $table->string('unit')->nullable()->after('product_description');
            $table->decimal('unit_price', 10, 2)->nullable()->after('unit');
            $table->boolean('is_manual')->default(false)->after('unit_price');

            // Re-add the foreign key constraint (allowing null)
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_description', 'unit', 'unit_price', 'is_manual']);
            $table->foreignId('product_id')->nullable(false)->change();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }
};
