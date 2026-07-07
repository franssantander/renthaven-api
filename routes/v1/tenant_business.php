<?php

use App\Http\Controllers\TenantBusinessController;
use Illuminate\Support\Facades\Route;


Route::prefix('tenant-business')->controller(TenantBusinessController::class)->name('tenant-business.')->group(function () {
    Route::post('register', 'registerBusiness')->name('register');

    Route::middleware('auth:api')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:tenant_business,view');
        Route::post('/', 'store')->name('store')->middleware('permission:tenant_business,create');
        Route::get('/{tenantBusiness}', 'show')->name('show')->middleware('permission:tenant_business,view');
        Route::put('/{tenantBusiness}', 'update')->name('update')->middleware('permission:tenant_business,update');
        Route::delete('/{tenantBusiness}', 'destroy')->name('destroy')->middleware('permission:tenant_business,delete');
    });
});