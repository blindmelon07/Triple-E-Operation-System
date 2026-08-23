<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('void_requests', function (Blueprint $table) {
            // Null = whole-sale void request (existing behavior).
            // Set   = single line-item void request for that sale.
            $table->foreignId('sale_item_id')->nullable()->after('sale_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('void_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_item_id');
        });
    }
};
