<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zk_devices', function (Blueprint $table) {
            // Bearer token the local bridge script authenticates with when
            // pushing attendance pulled off a LAN-only device (no ADMS/cloud
            // push support on the terminal itself).
            $table->string('api_token', 64)->nullable()->unique()->after('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('zk_devices', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });
    }
};
