<?php

use App\Http\Controllers\PropertyUnitController;
use Illuminate\Support\Facades\Route;


Route::prefix('property-unit')
    ->name('property-unit.')
    ->middleware('auth:api')
    ->controller(PropertyUnitController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{propertyUnit}', 'update')->name('update');
        Route::delete('/{propertyUnit}', 'destroy')->name('destroy');
        Route::get('/dashboard', 'dashboard')->name('dashboard');
    });