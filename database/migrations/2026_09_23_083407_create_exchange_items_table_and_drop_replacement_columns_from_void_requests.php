<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * No exchange requests exist in production yet (type='exchange' count is 0),
     * so the old singular replacement_* columns can be dropped outright instead
     * of migrated/backfilled.
     */
    public function up(): void
    {
        Schema::create('exchange_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('void_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->string('unit');
            $table->decimal('unit_price', 15, 2);
            $table->timestamps();
        });

        Schema::table('void_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replacement_product_id');
            $table->dropColumn(['replacement_quantity', 'replacement_unit', 'replacement_unit_price']);
        });
    }

    public function down(): void
    {
        Schema::table('void_requests', function (Blueprint $table) {
            $table->foreignId('replacement_product_id')->nullable()->after('type')
                ->constrained('products')->nullOnDelete();
            $table->decimal('replacement_quantity', 15, 2)->nullable()->after('replacement_product_id');
            $table->string('replacement_unit')->nullable()->after('replacement_quantity');
            $table->decimal('replacement_unit_price', 15, 2)->nullable()->after('replacement_unit');
        });

        Schema::dropIfExists('exchange_items');
    }
};
