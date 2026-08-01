<?php

use App\Http\Controllers\PropertyController;
use Illuminate\Support\Facades\Route;

Route::prefix('property')
    ->name('property.')
    ->middleware('auth:api')
    ->controller(PropertyController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:properties,view');
        Route::post('/', 'store')->name('store')->middleware('permission:properties,create');
        Route::put('/{property}', 'update')->name('update')->middleware('permission:properties,update');
        Route::put('/{property}/amenities', 'syncAmenities')->name('amenities.sync')->middleware('permission:properties,update');
        Route::post('/{property}/attachments', 'storeAttachments')->name('attachments.store')->middleware('permission:properties,create');
        Route::delete('/{property}/attachments/{attachment}', 'destroyAttachment')->name('attachments.destroy')->middleware('permission:properties,delete');
        Route::delete('/{property}', 'destroy')->name('destroy')->middleware('permission:properties,delete');
        Route::get('/dashboard', 'dashboard')->name('dashboard')->middleware('permission:properties,view');
        Route::get('/{property}', 'show')->name('show')->middleware('permission:properties,view');
    });
