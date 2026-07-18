<?php

use App\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('audit-logs')
    ->name('audit-logs.')
    ->middleware('auth:api')
    ->controller(AuditLogController::class)
    ->group(function () {
        Route::get('/mine', 'mine')->name('mine');
        Route::get('/', 'index')->name('index')->middleware('permission:audit_logs,view');
        Route::get('/{auditLog}', 'show')->name('show')->middleware('permission:audit_logs,view');
    });
