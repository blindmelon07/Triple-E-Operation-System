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
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('vehicle_id')->constrained()->nullOnDelete();
            $table->string('si_number')->nullable()->after('reference_number');
            $table->string('po_number')->nullable()->after('si_number');
            $table->string('item_name')->nullable()->after('maintenance_type_id');
            $table->decimal('quantity', 12, 2)->nullable()->after('item_name');
            $table->decimal('unit_price', 12, 2)->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn(['si_number', 'po_number', 'item_name', 'quantity', 'unit_price']);
        });
    }
};
