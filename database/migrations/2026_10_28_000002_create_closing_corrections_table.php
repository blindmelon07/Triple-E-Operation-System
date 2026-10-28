<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Requests to fix the closing amount of a register session that is already
     * closed (e.g. it was closed at ₱0 because the drawer wasn't counted).
     */
    public function up(): void
    {
        Schema::create('closing_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('original_closing_amount', 12, 2);
            $table->decimal('corrected_amount', 12, 2);
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closing_corrections');
    }
};
