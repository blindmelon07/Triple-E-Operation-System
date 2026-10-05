<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zk_devices', function (Blueprint $table) {
            // Set by the "Sync Attendance" button; the bridge script polls for
            // it and pulls from the device immediately instead of waiting for
            // its regular interval. Cleared when the bridge next uploads.
            $table->timestamp('sync_requested_at')->nullable()->after('last_seen_ip');
        });
    }

    public function down(): void
    {
        Schema::table('zk_devices', function (Blueprint $table) {
            $table->dropColumn('sync_requested_at');
        });
    }
};
