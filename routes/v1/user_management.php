<?php

use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;


Route::prefix('user-management')
    ->name('user-management.')
    ->middleware('auth:api')
    ->controller(UserManagementController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{user}', 'show')->name('show');
        Route::put('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
    });