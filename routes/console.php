<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('chat:prune')->daily()->at('03:00');

Schedule::call(function () {
    \App\Models\Client::onlyTrashed()
        ->where('deleted_at', '<=', now()->subDays(7))
        ->cursor()
        ->each(fn ($client) => $client->forceDelete());
})->daily()->at('04:00')->name('clients:prune-deleted');
