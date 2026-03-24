<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KnowledgeSourceController;
use App\Http\Controllers\Admin\PlanController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');



Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::get('clients/{client}/playground', [ClientController::class, 'playground'])->name('clients.playground');
    Route::get('clients/{client}/chat-history', [ClientController::class, 'chatHistory'])->name('clients.chat-history');
    Route::get('clients/{client}/usage-logs', [ClientController::class, 'usageLogs'])->name('clients.usage-logs');
    Route::get('clients/{client}/cache-entries', [ClientController::class, 'cacheEntries'])->name('clients.cache-entries');
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
