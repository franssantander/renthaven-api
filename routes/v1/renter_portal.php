<?php

use App\Http\Controllers\RenterPortalDashboardController;
use App\Http\Controllers\RenterPortalLeaseController;
use App\Http\Controllers\RenterPortalTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('renter-portal')
    ->name('renter-portal.')
    ->middleware('auth:api')
    ->group(function () {
        Route::get('/dashboard', [RenterPortalDashboardController::class, 'index'])->name('dashboard');
        Route::get('/lease', [RenterPortalLeaseController::class, 'index'])->name('lease');
        Route::get('/transactions', [RenterPortalTransactionController::class, 'index'])->name('transactions');
    });
