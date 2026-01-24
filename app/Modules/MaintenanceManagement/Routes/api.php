<?php

use Illuminate\Support\Facades\Route;

// If you prefer central route files, you can import them here instead of defining inline:
// require base_path('routes/api/v1/maitenance-management.php');
Route::prefix('v1')
    ->name('v1.')
    ->group(function () {
        Route::prefix('maitenance-management')
            ->name('maitenance-management.')
            // ->controller(ModuleController::class)
            ->group(function () {
                // Standard REST-ish endpoints
                
            });
    });