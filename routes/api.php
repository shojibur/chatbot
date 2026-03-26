<?php

use App\Http\Controllers\Api\ChatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('chat', function (Request $request) {
    $clientCode = $request->input('client_code', 'unknown');
    return [
        Limit::perMinute(10)->by($clientCode . '-' . $request->ip()),
        Limit::perHour(100)->by('global-' . $clientCode),
    ];
});

Route::prefix('v1')->group(function () {
    Route::post('chat', [ChatController::class, 'chat'])
        ->middleware('throttle:chat')
        ->name('api.chat');

    Route::get('widget-config/{clientCode}', [ChatController::class, 'widgetConfig'])
        ->middleware('throttle:120,1')
        ->name('api.widget-config');
});
