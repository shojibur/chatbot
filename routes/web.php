<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KnowledgeSourceController;
use App\Http\Controllers\Admin\PlanController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Temporary debug route — remove after diagnosing 502
Route::get('debug/client/{client}', function (App\Models\Client $client) {
    $steps = [];

    try {
        $steps['client_loaded'] = $client->name;
    } catch (\Throwable $e) {
        $steps['client_error'] = $e->getMessage();
    }

    try {
        $client->load('plan');
        $steps['plan_loaded'] = $client->plan?->name ?? 'no plan';
    } catch (\Throwable $e) {
        $steps['plan_error'] = $e->getMessage();
    }

    try {
        $steps['knowledge_sources_count'] = $client->knowledgeSources()->count();
    } catch (\Throwable $e) {
        $steps['knowledge_sources_error'] = $e->getMessage();
    }

    try {
        $steps['usage_logs_count'] = $client->usageLogs()->count();
    } catch (\Throwable $e) {
        $steps['usage_logs_error'] = $e->getMessage();
    }

    try {
        $steps['conversation_caches_count'] = $client->conversationCaches()->count();
    } catch (\Throwable $e) {
        $steps['conversation_caches_error'] = $e->getMessage();
    }

    try {
        $steps['knowledge_chunks_count'] = $client->knowledgeChunks()->count();
    } catch (\Throwable $e) {
        $steps['knowledge_chunks_error'] = $e->getMessage();
    }

    try {
        $steps['chat_sessions_count'] = $client->chatSessions()->count();
    } catch (\Throwable $e) {
        $steps['chat_sessions_error'] = $e->getMessage();
    }

    try {
        $steps['inertia_ssr_enabled'] = config('inertia.ssr.enabled');
    } catch (\Throwable $e) {
        $steps['config_error'] = $e->getMessage();
    }

    $steps['php_memory_limit'] = ini_get('memory_limit');
    $steps['php_version'] = PHP_VERSION;
    $steps['memory_used_mb'] = round(memory_get_peak_usage(true) / 1024 / 1024, 1);

    return response()->json($steps);
})->middleware('auth');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::get('clients/{client}/playground', [ClientController::class, 'playground'])->name('clients.playground');
    Route::get('clients/{client}/chat-history', [ClientController::class, 'chatHistory'])->name('clients.chat-history');
    Route::patch('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

    Route::post('clients/{client}/knowledge-sources', [KnowledgeSourceController::class, 'store'])
        ->name('clients.knowledge-sources.store');
    Route::patch('clients/{client}/knowledge-sources/{knowledgeSource}', [KnowledgeSourceController::class, 'update'])
        ->name('clients.knowledge-sources.update');
    Route::delete('clients/{client}/knowledge-sources/{knowledgeSource}', [KnowledgeSourceController::class, 'destroy'])
        ->name('clients.knowledge-sources.destroy');
    Route::post('clients/{client}/knowledge-sources/{knowledgeSource}/retry', [KnowledgeSourceController::class, 'retry'])
        ->name('clients.knowledge-sources.retry');
    Route::get('clients/{client}/knowledge-sources/{knowledgeSource}/chunks', [KnowledgeSourceController::class, 'chunks'])
        ->name('clients.knowledge-sources.chunks');

    Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
    Route::get('plans/create', [PlanController::class, 'create'])->name('plans.create');
    Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
    Route::get('plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
    Route::patch('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
    Route::delete('plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');
});

require __DIR__.'/settings.php';
