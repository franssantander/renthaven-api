<?php

use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;


Route::prefix('permission')
    ->name('permission.')
    ->middleware('auth:api')
    ->controller(PermissionController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:permission_management,view');
        Route::get('/{user}', 'show')->name('show')->middleware('permission:permission_management,view');
        Route::post('/sync', 'sync')->name('sync')->middleware('permission:permission_management,update');
        Route::post('/revoke-all', 'revokeAll')->name('revoke-all')->middleware('permission:permission_management,delete');
    });