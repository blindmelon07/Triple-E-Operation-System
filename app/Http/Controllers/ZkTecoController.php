<?php

namespace App\Http\Controllers;

use App\Models\ZkDevice;
use App\Services\ZkAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Implements the ZKTeco "Push SDK" / ADMS protocol that the device itself
 * speaks when configured with Cloud Server Setting -> this app's public URL.
 *
 * Reference flow (device-initiated, all plain text over HTTP):
 *   GET  /iclock/cdata?SN=...             handshake on boot / heartbeat
 *   POST /iclock/cdata?SN=...&table=ATTLOG  push of punch records
 *   GET  /iclock/getrequest?SN=...        device polls for pending commands
 *   POST /iclock/devicecmd?SN=...         device reports command results
 *
 * These endpoints are intentionally unauthenticated (the device can't do
 * Laravel auth) but are restricted to serial numbers registered and marked
 * active in zk_devices, and are routed outside the `web` middleware group
 * so no CSRF token is required.
 */
class ZkTecoController extends Controller
{
    public function __construct(protected ZkAttendanceService $attendanceService) {}

    /**
     * GET /iclock/cdata — device handshake/heartbeat.
     */
    public function handshake(Request $request): Response
    {
        $device = $this->resolveDevice($request);

        // Minimal ADMS handshake reply. Tells the device: no options changed,
        // check back every 30s, don't switch to real-time streaming.
        $body = implode("\n", [
            'GET OPTION FROM: '.($device?->serial_number ?? 'UNKNOWN'),
            'Stamp=9999',
            'OpStamp=9999',
            'ErrorDelay=30',
            'Delay=30',
            'TransFlag=1111000000',
            'TransInterval=1',
            'Realtime=1',
            'Encrypt=0',
        ])."\n";

        return response($body, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * POST /iclock/cdata?table=ATTLOG — the actual punch data push.
     */
    public function store(Request $request): Response
    {
        $device = $this->resolveDevice($request);
        $table = $request->query('table');

        if (! $device || ! $device->is_active) {
            Log::warning('ZKTeco: data push from unregistered/inactive device', [
                'sn' => $request->query('SN'),
                'ip' => $request->ip(),
                'table' => $table,
            ]);

            // Respond OK regardless so the device doesn't loop retrying forever;
            // the device just won't be receiving further command pushes.
            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        $device->markSeen($request->ip());

        if ($table === 'ATTLOG') {
            $body = trim((string) $request->getContent());
            $lines = $body === '' ? [] : preg_split('/\r\n|\r|\n/', $body);
            $processed = 0;

            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }

                if ($this->attendanceService->processLine($device, $line)) {
                    $processed++;
                }
            }

            return response('OK: '.$processed, 200)->header('Content-Type', 'text/plain');
        }

        // Other tables (OPERLOG, BIODATA, etc.) are acknowledged but not processed.
        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * GET /iclock/getrequest — device polling for queued commands.
     * We don't push commands down to the device (yet), so always "no work".
     */
    public function getRequest(Request $request): Response
    {
        $device = $this->resolveDevice($request);
        $device?->markSeen($request->ip());

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * POST /iclock/devicecmd — device reporting the result of a command it ran.
     */
    public function deviceCmd(Request $request): Response
    {
        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    protected function resolveDevice(Request $request): ?ZkDevice
    {
        $sn = (string) $request->query('SN', '');

        if ($sn === '') {
            return null;
        }

        return ZkDevice::firstOrCreate(
            ['serial_number' => $sn],
            ['name' => 'New device '.$sn, 'is_active' => false]
        );
    }
}
