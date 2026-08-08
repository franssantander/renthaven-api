<?php

use App\Http\Controllers\RenterController;
use Illuminate\Support\Facades\Route;

Route::prefix('renter')
    ->name('renter.')
    ->middleware('auth:api')
    ->controller(RenterController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:renter_tenants,view');
    });
