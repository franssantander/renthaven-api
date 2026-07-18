<?php

use App\Http\Controllers\RenterPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('renter-portal')
    ->name('renter-portal.')
    ->middleware('auth:api')
    ->controller(RenterPortalController::class)
    ->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
    });
