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
                Route::get('dashboard', 'dashboard');
                Route::get('{property:uuid}/available-units', 'getAvailableUnits');
                Route::apiResource('', PropertyController::class)
                    ->parameters(['' => 'property:uuid']);
            });
    });