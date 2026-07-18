<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')
    ->name('dashboard.')
    ->middleware('auth:api')
    ->controller(DashboardController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:dashboard,view');
        Route::get('/pending-approvals', 'pendingApprovals')->name('pending-approvals')->middleware('permission:payment_approvals,view');
        Route::get('/recent-activity', 'recentActivity')->name('recent-activity')->middleware('permission:ledger,view');
    });
