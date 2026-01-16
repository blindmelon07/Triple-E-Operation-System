<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the approve_quotation permission
        Permission::firstOrCreate(
            ['name' => 'approve_quotation'],
            ['guard_name' => 'web']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete the approve_quotation permission
        Permission::where('name', 'approve_quotation')->delete();
    }
};
