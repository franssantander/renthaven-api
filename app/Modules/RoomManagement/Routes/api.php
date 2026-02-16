<?php

use App\Modules\RoomManagement\Http\Controllers\API\v1\RoomManagementController;
use Illuminate\Support\Facades\Route;

// If you prefer central route files, you can import them here instead of defining inline:
// require base_path('routes/api/v1/room-management.php');
Route::prefix('v1')
    ->name('v1.')
    ->middleware('auth:api')
    ->group(function () {
        Route::prefix('room-management')
            ->name('room-management.')
            ->controller(RoomManagementController::class)
            ->group(function () {
                Route::put('/move-tenant', 'update');
                Route::delete('/delete', 'destroy');
                Route::apiResource('', RoomManagementController::class)
                    ->parameters(['' => 'room:uuid']);
            });
    });