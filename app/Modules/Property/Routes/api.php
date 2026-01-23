<?php

use App\Modules\Property\Http\Controllers\API\v1\PropertyController;
use Illuminate\Support\Facades\Route;

// If you prefer central route files, you can import them here instead of defining inline:
// require base_path('routes/api/v1/property.php');
Route::prefix('v1')
    ->name('v1.')
    ->middleware('auth:api')
    ->group(function () {
        Route::prefix('property')
            ->name('property.')
            ->controller(PropertyController::class)
            ->group(function () {
                // Standard REST-ish endpoints
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::get('{property:uuid}', 'show')->name('show');
                Route::patch('{property:uuid}', 'update')->name('update');
                // Route::delete('{id}', 'destroy')->name('destroy');
        
                // Examples for full replace vs partial update patterns
                // Other related route here . .
            });
    });