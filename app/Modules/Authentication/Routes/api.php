<?php

use App\Modules\Authentication\Http\Controllers\API\v1\LoginController;
use Illuminate\Support\Facades\Route;

// If you prefer central route files, you can import them here instead of defining inline:
// require base_path('routes/api/v1/authentication.php');
Route::prefix('v1')
    ->name('v1.')
    ->group(function () {
        Route::prefix('authentication')
            ->name('authentication.')
            ->group(function () {
                // Standard REST-ish endpoints
                // Route::get('/', 'index')->name('index');
                // Route::post('/', 'store')->name('store');
                // Route::get('{id}', 'show')->name('show');
                // Route::patch('{id}', 'update')->name('update');
                // Route::delete('{id}', 'destroy')->name('destroy');
        
                Route::post('login', LoginController::class)->name('login');
                Route::middleware('auth:api')->group(function () {
                    Route::post('logout');
                });
            });
    });
