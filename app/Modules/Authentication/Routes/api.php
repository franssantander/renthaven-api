<?php

use App\Modules\Authentication\Http\Controllers\API\v1\LoginController;
use App\Modules\Authentication\Http\Controllers\API\v1\LogoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('v1.')
    ->group(function () {
        Route::prefix('authentication')
            ->name('authentication.')
            ->group(function () {
                Route::post('login', LoginController::class)->name('login');

                Route::middleware('auth:api')->group(function () {
                    Route::post('/logout', LogoutController::class)->name('logout');
                });
            });
    });
