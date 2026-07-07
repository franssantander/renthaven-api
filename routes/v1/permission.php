<?php

use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;


Route::prefix('permission')
    ->name('permission.')
    ->middleware('auth:api')
    ->controller(PermissionController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{user}', 'show')->name('show');
        Route::put('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
        Route::post('/sync', 'sync')->name('sync');
        Route::post('/revoke-all', 'revokeAll')->name('revoke-all');
    });