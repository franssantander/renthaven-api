<?php

use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;


Route::prefix('roles')
    ->name('roles.')
    ->middleware(['auth:api', 'permission:user_management,view'])
    ->controller(RoleController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
    });
