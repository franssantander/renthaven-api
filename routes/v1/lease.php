<?php

use App\Http\Controllers\LeaseController;
use Illuminate\Support\Facades\Route;

Route::prefix('lease')
    ->name('lease.')
    ->middleware('auth:api')
    ->controller(LeaseController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:renter_tenants,view');
        Route::post('/', 'store')->name('store')->middleware('permission:renter_tenants,create');
        Route::put('/{lease}', 'update')->name('update')->middleware('permission:renter_tenants,update');
        Route::put('/{lease}/renew', 'renew')->name('renew')->middleware('permission:renter_tenants,update');
        Route::delete('/{lease}', 'destroy')->name('destroy')->middleware('permission:renter_tenants,delete');
    });
