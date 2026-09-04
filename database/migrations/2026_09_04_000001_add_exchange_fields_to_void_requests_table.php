<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('void_requests', function (Blueprint $table) {
            // 'void'     = remove the sale (or one line item) entirely — existing behavior.
            // 'exchange' = swap one line item for a different product; the replacement_*
            //              columns below describe what it should be swapped for.
            $table->string('type')->default('void')->after('sale_item_id');

            $table->foreignId('replacement_product_id')->nullable()->after('type')
                ->constrained('products')->nullOnDelete();
            $table->decimal('replacement_quantity', 15, 2)->nullable()->after('replacement_product_id');
            $table->string('replacement_unit')->nullable()->after('replacement_quantity');
            $table->decimal('replacement_unit_price', 15, 2)->nullable()->after('replacement_unit');
        });
    }

    public function down(): void
    {
        Schema::table('void_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replacement_product_id');
            $table->dropColumn(['type', 'replacement_quantity', 'replacement_unit', 'replacement_unit_price']);
        });
    }
};
