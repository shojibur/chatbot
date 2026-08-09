<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\LeadController as ApiLeadController;
use App\Http\Controllers\Mobile\AuthController as MobileAuthController;
use App\Http\Controllers\Mobile\DashboardController as MobileDashboardController;
use App\Http\Controllers\Mobile\KnowledgeSourceController as MobileKnowledgeSourceController;
use App\Http\Controllers\Mobile\LeadController as MobileLeadController;
use App\Http\Controllers\Mobile\SessionController as MobileSessionController;
use App\Http\Controllers\Mobile\SettingsController as MobileSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('chat', [ChatController::class, 'chat'])
        ->middleware('throttle:chat')
        ->name('api.chat');
    Route::get('chat/session/messages', [ChatController::class, 'sessionMessages'])
        ->middleware('throttle:120,1')
        ->name('api.chat.session-messages');

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

Route::prefix('mobile')->name('api.mobile.')->group(function () {
    Route::post('auth/login', [MobileAuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [MobileAuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [MobileAuthController::class, 'me'])->name('me');

        Route::get('dashboard', MobileDashboardController::class)->name('dashboard');
        Route::get('sessions', [MobileSessionController::class, 'index'])->name('sessions.index');
        Route::get('sessions/{session}/messages', [MobileSessionController::class, 'messages'])->name('sessions.messages');
        Route::post('sessions/{session}/takeover', [MobileSessionController::class, 'takeover'])->name('sessions.takeover');
        Route::post('sessions/{session}/release', [MobileSessionController::class, 'releaseTakeover'])->name('sessions.release');
        Route::post('sessions/{session}/messages', [MobileSessionController::class, 'sendMessage'])->name('sessions.send-message');
        Route::get('leads', [MobileLeadController::class, 'index'])->name('leads.index');
        Route::get('leads/{lead}', [MobileLeadController::class, 'show'])->name('leads.show');
        Route::patch('leads/{lead}/status', [MobileLeadController::class, 'updateStatus'])->name('leads.status');
        Route::get('knowledge-sources', [MobileKnowledgeSourceController::class, 'index'])->name('knowledge-sources.index');
        Route::post('knowledge-sources', [MobileKnowledgeSourceController::class, 'store'])->name('knowledge-sources.store');
        Route::delete('knowledge-sources/{knowledgeSource}', [MobileKnowledgeSourceController::class, 'destroy'])->name('knowledge-sources.destroy');
        Route::post('knowledge-sources/{knowledgeSource}/retry', [MobileKnowledgeSourceController::class, 'retry'])->name('knowledge-sources.retry');
        Route::get('settings', [MobileSettingsController::class, 'show'])->name('settings.show');
        Route::patch('settings/widget', [MobileSettingsController::class, 'updateWidget'])->name('settings.widget');
    });
});
