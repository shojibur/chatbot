<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KnowledgeSourceController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Widget\IframeWidgetController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::get('widget/iframe/{clientCode}', [IframeWidgetController::class, 'show'])
    ->name('widget.iframe');



Route::middleware(['auth', 'verified', EnsureUserIsAdmin::class])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::get('clients/{client}/playground', [ClientController::class, 'playground'])->name('clients.playground');
    Route::get('clients/{client}/chat-history', [ClientController::class, 'chatHistory'])->name('clients.chat-history');
    Route::get('clients/{client}/chat-history/{session}/messages', [ClientController::class, 'chatSessionMessages'])->name('clients.chat-history.messages');
    Route::get('clients/{client}/usage-logs', [ClientController::class, 'usageLogs'])->name('clients.usage-logs');
    Route::get('clients/{client}/cache-entries', [ClientController::class, 'cacheEntries'])->name('clients.cache-entries');
    Route::delete('clients/{client}/cache-entries', [ClientController::class, 'clearCache'])->name('clients.cache-entries.clear');
    Route::delete('clients/{client}/cache-entries/{cacheEntry}', [ClientController::class, 'destroyCacheEntry'])->name('clients.cache-entries.destroy');
    Route::patch('clients/{client}/iframe-settings', [ClientController::class, 'updateIframeSettings'])->name('clients.iframe-settings.update');
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

    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');
    Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

    Route::resource('users', UserController::class);
});

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureUserIsClient::class])->prefix('portal')->name('portal.')->group(function () {
    Route::get('dashboard', \App\Http\Controllers\Portal\DashboardController::class)->name('dashboard');

    Route::post('knowledge-sources', [\App\Http\Controllers\Portal\KnowledgeSourceController::class, 'store'])
        ->name('knowledge-sources.store');
    Route::patch('knowledge-sources/{knowledgeSource}', [\App\Http\Controllers\Portal\KnowledgeSourceController::class, 'update'])
        ->name('knowledge-sources.update');
    Route::delete('knowledge-sources/{knowledgeSource}', [\App\Http\Controllers\Portal\KnowledgeSourceController::class, 'destroy'])
        ->name('knowledge-sources.destroy');
    Route::post('knowledge-sources/{knowledgeSource}/retry', [\App\Http\Controllers\Portal\KnowledgeSourceController::class, 'retry'])
        ->name('knowledge-sources.retry');
    Route::get('knowledge-sources/{knowledgeSource}/chunks', [\App\Http\Controllers\Portal\KnowledgeSourceController::class, 'chunks'])
        ->name('knowledge-sources.chunks');

    Route::get('playground', \App\Http\Controllers\Portal\PlaygroundController::class)->name('playground');
    Route::post('playground/chat', [\App\Http\Controllers\Portal\PlaygroundChatController::class, 'chat'])->name('playground.chat');
    Route::get('subscription', \App\Http\Controllers\Portal\SubscriptionController::class)->name('subscription');

    // Chat History
    Route::get('chat-history', [\App\Http\Controllers\Portal\ChatHistoryController::class, 'index'])->name('chat-history');
    Route::get('chat-history/{session}/messages', [\App\Http\Controllers\Portal\ChatHistoryController::class, 'messages'])->name('chat-history.messages');

    // Leads
    Route::get('leads', [\App\Http\Controllers\Portal\LeadController::class, 'index'])->name('leads.index');
    Route::get('leads/{lead}', [\App\Http\Controllers\Portal\LeadController::class, 'show'])->name('leads.show');
    Route::patch('leads/{lead}/status', [\App\Http\Controllers\Portal\LeadController::class, 'updateStatus'])->name('leads.status');
    Route::delete('leads/{lead}', [\App\Http\Controllers\Portal\LeadController::class, 'destroy'])->name('leads.destroy');
});

require __DIR__.'/settings.php';
