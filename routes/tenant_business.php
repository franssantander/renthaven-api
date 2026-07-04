<?php

use App\Http\Controllers\TenantBusinessController;
use Illuminate\Support\Facades\Route;


Route::prefix('tenant-business')->controller(TenantBusinessController::class)->name('tenant-business.')->group(function () {
    Route::post('register', 'registerBusiness')->name('register');

    Route::middleware(['auth:api', 'role:super_admin,system_admin,admin,staff'])->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{tenantBusiness}', 'show')->name('show');
        // Route::put('/{tenantBusiness}', 'update')->name('update');
        // Route::delete('/{tenantBusiness}', 'destroy')->name('destroy');
    });
});
