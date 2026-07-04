<?php

use App\Http\Controllers\TenantBusinessController;
use Illuminate\Support\Facades\Route;


Route::prefix('tenant-business')->name('tenant-business.')->group(function () {
    Route::post('register', [TenantBusinessController::class, 'registerBusiness']);


    Route::middleware('auth:api')->group(function () {});
});