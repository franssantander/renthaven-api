<?php

use App\Http\Controllers\MaintenanceRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('maintenance-request')
    ->name('maintenance-request.')
    ->middleware('auth:api')
    ->controller(MaintenanceRequestController::class)
    ->group(function () {
        Route::post('/', 'store')->name('store');
        Route::get('/mine', 'mine')->name('mine');
        Route::get('/dashboard', 'dashboard')->name('dashboard')->middleware('permission:maintenance,view');
        Route::get('/', 'index')->name('index')->middleware('permission:maintenance,view');
        Route::put('/{maintenanceRequest}/status', 'updateStatus')->name('status')->middleware('permission:maintenance,update');
        Route::get('/{maintenanceRequest}', 'show')->name('show');
    });
