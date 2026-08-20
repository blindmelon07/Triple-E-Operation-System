<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zk_attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zk_device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pin');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('punched_at');
            // Raw values as sent by the device (ATTLOG columns 3 & 4):
            // status: 0 check-in, 1 check-out, 2 break-out, 3 break-in, 4 OT-in, 5 OT-out (varies by device)
            $table->unsignedTinyInteger('status')->nullable();
            // verify: 1 fingerprint, 15 face, 4 card, etc.
            $table->unsignedTinyInteger('verify_type')->nullable();
            $table->text('raw_line')->nullable();
            $table->timestamps();

            // A device never sends the exact same PIN+timestamp twice; guards re-processing
            // if a device retransmits a batch it didn't get an ACK for.
            $table->unique(['zk_device_id', 'pin', 'punched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zk_attendance_logs');
    }
};
