<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_unit_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('unit');
            $table->decimal('price', 10, 2);
            $table->decimal('conversion_factor', 12, 4)->default(1);
            $table->timestamps();

            $table->unique(['product_id', 'unit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_unit_prices');
    }
};
