<?php

use App\Http\Controllers\PropertyController;
use Illuminate\Support\Facades\Route;


Route::prefix('property')
    ->name('property.')
    ->middleware('auth:api')
    ->controller(PropertyController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{property}', 'update')->name('update');
        Route::put('/{property}/amenities', 'syncAmenities')->name('amenities.sync');
        Route::post('/{property}/attachments', 'storeAttachments')->name('attachments.store')->middleware('permission:properties,create');
        Route::delete('/{property}/attachments/{attachment}', 'destroyAttachment')->name('attachments.destroy')->middleware('permission:properties,delete');
        Route::delete('/{property}', 'destroy')->name('destroy');
        Route::get('/dashboard', 'dashboard')->name('dashboard');
        Route::get('/{property}', 'show')->name('show');
    });