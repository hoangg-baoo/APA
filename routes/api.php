<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IotTelemetryController;

Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'API routes file OK',
    ]);
});

// Phase 2 - IoT telemetry (stateless, no CSRF)
// throttle:60,1 = tối đa 60 requests/phút/IP (phù hợp tần số IoT 1 lần/giây)
Route::post('/iot/tanks/{tank}/telemetry', [IotTelemetryController::class, 'store'])
    ->whereNumber('tank')
    ->middleware('throttle:60,1')
    ->name('api.iot.telemetry.store');