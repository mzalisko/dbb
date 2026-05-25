<?php

use App\Livewire\Dashboard;
use App\Livewire\Clients;
use App\Livewire\Sites;
use App\Livewire\Users;
use App\Livewire\Settings;
use App\Livewire\ActivityLog;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Clients
    Route::get('/clients', Clients\Index::class)->name('clients.index');
    Route::get('/clients/{client}', Clients\Show::class)->name('clients.show');

    // Sites
    Route::get('/sites', Sites\Index::class)->name('sites.index');
    Route::get('/sites/{site}', Sites\Show::class)->name('sites.show');

    // Team / Users
    Route::get('/team', Users\Index::class)->name('users.index');

    // Activity Log
    Route::get('/activity', ActivityLog::class)->name('activity.index');

    // Settings
    Route::get('/settings', Settings::class)->name('settings');
});

if (app()->isLocal() || app()->runningUnitTests()) {
    Route::get('/_design', fn() => view('pages.design-system'))->middleware('web')->name('design-system');
}
