<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records mid-shift cash added to an already-open register (e.g. the cashier
     * needed more starting change). Kept as a separate ledger rather than mutating
     * cash_register_sessions.opening_amount so that column stays the true amount
     * counted at open, and every top-up stays individually auditable.
     */
    public function up(): void
    {
        Schema::create('cash_register_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_adjustments');
    }
};
