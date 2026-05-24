<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

if (app()->isLocal() || app()->runningUnitTests()) {
    Route::get('/_design', fn() => view('pages.design-system'))->middleware('web')->name('design-system');
}
