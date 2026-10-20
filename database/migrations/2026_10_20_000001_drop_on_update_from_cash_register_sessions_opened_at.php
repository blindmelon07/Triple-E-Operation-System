<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MySQL/MariaDB silently gave the first NOT NULL timestamp column
     * `ON UPDATE CURRENT_TIMESTAMP`, so every sale added to a session
     * overwrote opened_at with the time of that update.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE cash_register_sessions MODIFY opened_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cash_register_sessions MODIFY opened_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
};
