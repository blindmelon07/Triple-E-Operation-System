<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ZkDevice;
use App\Services\ZkAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives attendance punches from the local bridge script (bridge/zkteco_bridge.py)
 * that runs on a PC on the same LAN as a ZKTeco terminal which has no ADMS/cloud
 * push capability of its own. The bridge pulls logs off the device over the LAN
 * and forwards them here over HTTPS.
 *
 * Auth: `Authorization: Bearer <zk_devices.api_token>` — one token per device,
 * generated automatically when the device is registered in the admin panel.
 */
class ZkBridgeController extends Controller
{
    public function __construct(protected ZkAttendanceService $attendanceService) {}

    public function store(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Missing bearer token.'], 401);
        }

        $device = ZkDevice::where('api_token', $token)->first();

        if (! $device) {
            Log::warning('ZKTeco bridge: unknown token used', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid token.'], 401);
        }

        if (! $device->is_active) {
            return response()->json(['message' => 'Device is not active.'], 403);
        }

        $device->markSeen($request->ip());

        $lines = $request->input('lines', []);

        if (! is_array($lines)) {
            return response()->json(['message' => '"lines" must be an array of ATTLOG-formatted strings.'], 422);
        }

        $processed = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            if (! is_string($line) || trim($line) === '') {
                $skipped++;

                continue;
            }

            if ($this->attendanceService->processLine($device, $line)) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        return response()->json([
            'message' => 'OK',
            'processed' => $processed,
            'skipped' => $skipped,
        ]);
    }
}
