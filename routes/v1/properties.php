<?php

use App\Http\Controllers\PropertyController;
use Illuminate\Support\Facades\Route;


Route::prefix('property')
    ->name('property.')
    ->middleware('auth:api')
    ->controller(PropertyController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{property}', 'update')->name('update');
        Route::delete('/{property}', 'destroy')->name('destroy');
        Route::get('/dashboard', 'dashboard')->name('dashboard');
    });