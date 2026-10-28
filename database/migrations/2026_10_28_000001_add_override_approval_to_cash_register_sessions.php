<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Closing a register at ₱0 while cash is expected needs a manager's
     * sign-off; this records who gave it and when.
     */
    public function up(): void
    {
        Schema::table('cash_register_sessions', function (Blueprint $table) {
            $table->foreignId('override_approved_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->timestamp('override_approved_at')->nullable()->after('override_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('cash_register_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('override_approved_by');
            $table->dropColumn('override_approved_at');
        });
    }
};
