<?php

use App\Modules\RenterManagement\Http\Controllers\API\v1\RenterManagementController;
use Illuminate\Support\Facades\Route;

// If you prefer central route files, you can import them here instead of defining inline:
// require base_path('routes/api/v1/renter-management.php');
Route::prefix('v1')
    ->name('v1.')
    ->middleware('auth:api')
    ->group(function () {
        Route::prefix('tenant-management')
            ->name('tenant-management.')
            ->controller(RenterManagementController::class)
            ->group(function () {
                Route::get('dashboard', 'dashboard');
                Route::apiResource('', RenterManagementController::class)
                    ->parameters(['' => 'lease:uuid']);
            });
    });