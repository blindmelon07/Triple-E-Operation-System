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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number')->unique();
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('color')->nullable();
            $table->string('vin')->nullable()->unique();
            $table->string('engine_number')->nullable();
            $table->enum('fuel_type', ['gasoline', 'diesel', 'electric', 'hybrid'])->default('gasoline');
            $table->enum('transmission', ['automatic', 'manual'])->default('automatic');
            $table->integer('current_mileage')->default(0);
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_cost', 12, 2)->nullable();
            $table->enum('status', ['active', 'maintenance', 'inactive', 'sold'])->default('active');
            $table->string('assigned_driver')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
