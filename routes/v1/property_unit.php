<?php

use App\Http\Controllers\PropertyUnitController;
use Illuminate\Support\Facades\Route;


Route::prefix('property-unit')
    ->name('property-unit.')
    ->middleware('auth:api')
    ->controller(PropertyUnitController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:properties,view');
        Route::post('/', 'store')->name('store')->middleware('permission:properties,create');
        Route::put('/{propertyUnit}', 'update')->name('update')->middleware('permission:properties,update');
        Route::put('/{propertyUnit}/amenities', 'syncAmenities')->name('amenities.sync')->middleware('permission:properties,update');
        Route::post('/{propertyUnit}/attachments', 'storeAttachments')->name('attachments.store')->middleware('permission:properties,create');
        Route::delete('/{propertyUnit}/attachments/{attachment}', 'destroyAttachment')->name('attachments.destroy')->middleware('permission:properties,delete');
        Route::delete('/{propertyUnit}', 'destroy')->name('destroy')->middleware('permission:properties,delete');
        Route::get('/dashboard', 'dashboard')->name('dashboard');
        Route::get('/{propertyUnit}', 'show')->name('show')->middleware('permission:properties,view');
    });