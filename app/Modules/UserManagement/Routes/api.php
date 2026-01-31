<?php

use App\Modules\UserManagement\Http\Controllers\API\v1\UserManagementController;
use Illuminate\Support\Facades\Route;

// If you prefer central route files, you can import them here instead of defining inline:
// require base_path('routes/api/v1/user-management.php');
Route::prefix('v1')
    ->name('v1.')
    ->group(function () {
        Route::prefix('user-management')
            ->name('user-management.')
            ->controller(UserManagementController::class)
            ->group(function () {
                Route::apiResource('/', UserManagementController::class)
                    ->parameters(['' => 'id']);
            });
    });