<?php

use App\Modules\BillManagement\Http\Controllers\API\v1\BillManagementController;
use Illuminate\Support\Facades\Route;

// If you prefer central route files, you can import them here instead of defining inline:
// require base_path('routes/api/v1/bill-management.php');
Route::prefix('v1')
    ->name('v1.')
    ->middleware('auth:api')
    ->group(function () {
        Route::prefix('bill-management')
            ->name('bill-management.')
            ->controller(BillManagementController::class)
            ->group(function () {
                Route::get('transactions', 'getTransaction')->name('transactions');
                Route::apiResource('', BillManagementController::class)
                    ->parameters(['' => 'bill:uuid']);
            });
    });