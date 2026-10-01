<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('date_change_requests', function (Blueprint $table) {
            $table->id();
            $table->morphs('record');
            $table->string('field');
            $table->date('old_date')->nullable();
            $table->date('new_date');
            $table->string('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('requested_by_id')->constrained('users');
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('date_change_requests');
    }
};
