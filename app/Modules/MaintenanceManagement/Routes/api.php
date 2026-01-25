<?php

use App\Modules\MaintenanceManagement\Http\Controllers\API\v1\MaintenanceManagementController;
use Illuminate\Support\Facades\Route;

// If you prefer central route files, you can import them here instead of defining inline:
// require base_path('routes/api/v1/maintenance-management.php');
Route::prefix('v1')
    ->name('v1.')
    ->group(function () {
        Route::prefix('maintenance-management')
            ->name('maintenance-management.')
            // ->controller(ModuleController::class)
            ->group(function () {
                Route::apiResource('', MaintenanceManagementController::class);
                // Standard REST-ish endpoints
                // Route::get('/', 'index')->name('index');
                // Route::post('/', 'store')->name('store');
                // Route::get('{id}', 'show')->name('show');
                // Route::patch('{id}', 'update')->name('update');
                // Route::delete('{id}', 'destroy')->name('destroy');
        
                // Examples for full replace vs partial update patterns
                // Other related route here . .
            });
    });