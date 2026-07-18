<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')
    ->name('dashboard.')
    ->middleware('auth:api')
    ->controller(DashboardController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:dashboard,view');
    });
