<?php

use App\Modules\TenantManagement\Http\Controllers\API\v1\TenantManagementController;
use Illuminate\Support\Facades\Route;

// If you prefer central route files, you can import them here instead of defining inline:
// require base_path('routes/api/v1/tenant-management.php');
Route::prefix('v1')
    ->name('v1.')
    ->middleware('auth:api')
    ->group(function () {
        Route::prefix('tenant-management')
            ->name('tenant-management.')
            ->controller(TenantManagementController::class)
            ->group(function () {
                Route::apiResource('', TenantManagementController::class)
                    ->parameters(['' => 'lease:uuid']);
            });
    });