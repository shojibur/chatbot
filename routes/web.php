<?php

use App\Http\Controllers\Admin\ClientController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [ClientController::class, 'index'])->name('dashboard');
    Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
    Route::patch('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
});

require __DIR__.'/settings.php';
