<?php

use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;


Route::prefix('user-management')
    ->name('user-management.')
    ->middleware('auth:api')
    ->controller(UserManagementController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:user_management,view');
        Route::post('/', 'store')->name('store')->middleware('permission:user_management,create');
        Route::get('/{user}', 'show')->name('show')->middleware('permission:user_management,view');
        Route::put('/{user}', 'update')->name('update')->middleware('permission:user_management,update');
        Route::delete('/{user}', 'destroy')->name('destroy')->middleware('permission:user_management,delete');
    });