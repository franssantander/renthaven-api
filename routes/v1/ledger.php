<?php

use App\Http\Controllers\LedgerController;
use Illuminate\Support\Facades\Route;

Route::prefix('ledger')
    ->name('ledger.')
    ->middleware('auth:api')
    ->controller(LedgerController::class)
    ->group(function () {
        Route::get('/mine', 'mine')->name('mine');
        Route::get('/dashboard', 'dashboard')->name('dashboard')->middleware('permission:renter_tenants,view');
        Route::get('/', 'index')->name('index')->middleware('permission:renter_tenants,view');
        Route::put('/{ledgerEntry}/pay', 'markPaid')->name('pay')->middleware('permission:renter_tenants,update');
    });
