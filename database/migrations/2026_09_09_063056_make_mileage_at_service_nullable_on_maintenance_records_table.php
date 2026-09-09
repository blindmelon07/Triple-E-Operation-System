<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't enforce NOT NULL via ALTER COLUMN; existing rows
            // and inserts already work with NULL once the app stops sending it.
            return;
        }

        DB::statement('ALTER TABLE maintenance_records MODIFY mileage_at_service INT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        DB::statement('UPDATE maintenance_records SET mileage_at_service = 0 WHERE mileage_at_service IS NULL');
        DB::statement('ALTER TABLE maintenance_records MODIFY mileage_at_service INT NOT NULL');
    }
};
