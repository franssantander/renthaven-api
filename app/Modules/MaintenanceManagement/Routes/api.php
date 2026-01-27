<?php

use App\Modules\MaintenanceManagement\Http\Controllers\API\v1\MaintenanceManagementController;
use Illuminate\Support\Facades\Route;

// If you prefer central route files, you can import them here instead of defining inline:
// require base_path('routes/api/v1/maintenance-management.php');
Route::prefix('v1')
    ->name('v1.')
    ->middleware('auth:api')
    ->group(function () {
        Route::prefix('maintenance-management')
            ->name('maintenance-management.')
            ->controller(MaintenanceManagementController::class)
            ->group(function () {
                Route::get('dashboard', 'dashboard')->name('dashboard');
                Route::apiResource('', MaintenanceManagementController::class)
                    ->parameters(['' => 'maintenance_property:uuid']);
            });
    });