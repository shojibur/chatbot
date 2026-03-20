<?php

use App\Http\Controllers\Api\ChatController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('chat', [ChatController::class, 'chat'])
        ->middleware('throttle:60,1')
        ->name('api.chat');

    Route::get('widget-config/{clientCode}', [ChatController::class, 'widgetConfig'])
        ->middleware('throttle:120,1')
        ->name('api.widget-config');
});
