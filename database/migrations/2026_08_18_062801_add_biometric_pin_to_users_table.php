<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The enrollment number (PIN) assigned to the employee on the ZKTeco
            // device's own keypad/fingerprint enrollment. This is what device
            // punches are matched against, not the users.id.
            $table->string('biometric_pin')->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('biometric_pin');
        });
    }
};
