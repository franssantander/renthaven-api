<?php

use App\Http\Controllers\PropertyUnitController;
use Illuminate\Support\Facades\Route;


Route::prefix('property-unit')
    ->name('property-unit.')
    ->middleware('auth:api')
    ->controller(PropertyUnitController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:properties,view');
        Route::post('/', 'store')->name('store')->middleware('permission:properties,create');
        Route::put('/{propertyUnit}', 'update')->name('update')->middleware('permission:properties,update');
        Route::delete('/{propertyUnit}', 'destroy')->name('destroy')->middleware('permission:properties,delete');
        Route::get('/dashboard', 'dashboard')->name('dashboard');
    });