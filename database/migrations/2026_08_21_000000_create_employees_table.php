<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Optional link to a TOS login account. Nullable because an
            // employee who only ever punches the biometric device (never
            // logs into TOS) doesn't need one. nullOnDelete so revoking a
            // login doesn't wipe out the employee's attendance/payroll
            // history — it just severs the login link.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('email')->nullable();

            // Moved from users.biometric_pin / users.attendance_log_mode.
            $table->string('biometric_pin')->nullable()->unique();
            $table->string('attendance_log_mode', 10)->default('two');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
