<?php

use App\Http\Controllers\AmenityController;
use Illuminate\Support\Facades\Route;

Route::prefix('amenity')
    ->name('amenity.')
    ->middleware('auth:api')
    ->controller(AmenityController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:properties,view');
        Route::post('/', 'store')->name('store');
        Route::put('/{amenity}', 'update')->name('update');
        Route::delete('/{amenity}', 'destroy')->name('destroy');
    });
