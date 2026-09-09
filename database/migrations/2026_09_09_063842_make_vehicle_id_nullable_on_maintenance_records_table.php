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
            // SQLite doesn't enforce NOT NULL via ALTER COLUMN — nothing to do.
            return;
        }

        DB::statement('ALTER TABLE maintenance_records DROP FOREIGN KEY maintenance_records_vehicle_id_foreign');
        DB::statement('ALTER TABLE maintenance_records MODIFY vehicle_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE maintenance_records ADD CONSTRAINT maintenance_records_vehicle_id_foreign FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE');
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

        DB::statement('DELETE FROM maintenance_records WHERE vehicle_id IS NULL');
        DB::statement('ALTER TABLE maintenance_records DROP FOREIGN KEY maintenance_records_vehicle_id_foreign');
        DB::statement('ALTER TABLE maintenance_records MODIFY vehicle_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE maintenance_records ADD CONSTRAINT maintenance_records_vehicle_id_foreign FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON DELETE CASCADE');
    }
};
