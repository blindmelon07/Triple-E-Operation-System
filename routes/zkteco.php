<?php

use App\Http\Controllers\Api\ZkBridgeController;
use App\Http\Controllers\ZkTecoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ZKTeco ADMS/Push routes
|--------------------------------------------------------------------------
|
| These paths are fixed by the ZKTeco device firmware (Cloud Server Setting
| points at this app's domain, and the device itself always requests
| /iclock/...). Registered without the `web` group so there's no CSRF/
| session overhead the device can't satisfy anyway; access is instead
| controlled by the registered-and-active check in ZkTecoController.
|
*/

// Unauthenticated by necessity (see above), so throttled by IP instead:
// legitimate devices poll every 30s at most, well under this ceiling.
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/iclock/cdata', [ZkTecoController::class, 'handshake']);
    Route::post('/iclock/cdata', [ZkTecoController::class, 'store']);
    Route::get('/iclock/getrequest', [ZkTecoController::class, 'getRequest']);
    Route::post('/iclock/devicecmd', [ZkTecoController::class, 'deviceCmd']);
});

/*
|--------------------------------------------------------------------------
| Local bridge ingestion
|--------------------------------------------------------------------------
|
| Used by bridge/zkteco_bridge.py — a script that runs on a PC on the same
| LAN as a device with no cloud push capability, and forwards punches it
| pulled locally. Authenticated with a per-device bearer token instead of
| the SN whitelist the direct-push /iclock routes rely on.
|
*/
Route::middleware('throttle:30,1')->post('/api/zkteco/attendance', [ZkBridgeController::class, 'store']);
