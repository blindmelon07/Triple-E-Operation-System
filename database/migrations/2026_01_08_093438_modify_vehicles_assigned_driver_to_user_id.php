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
        Schema::table('vehicles', function (Blueprint $table) {
            // Drop the old string column
            $table->dropColumn('assigned_driver');

            // Add new foreign key column
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['assigned_driver_id']);
            $table->dropColumn('assigned_driver_id');
            $table->string('assigned_driver')->nullable();
        });
    }
};
