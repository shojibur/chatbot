<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\LeadController as ApiLeadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('chat', [ChatController::class, 'chat'])
        ->middleware('throttle:chat')
        ->name('api.chat');

    Route::get('widget-config/{clientCode}', [ChatController::class, 'widgetConfig'])
        ->middleware('throttle:120,1')
        ->name('api.widget-config');

    Route::post('leads', [ApiLeadController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('api.leads.store');

    Route::post('leads/process', [ApiLeadController::class, 'process'])
        ->middleware('throttle:30,1')
        ->name('api.leads.process');
});
