<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')
    ->name('notifications.')
    ->middleware('auth:api')
    ->controller(NotificationController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/unread-count', 'unreadCount')->name('unread-count');
        Route::put('/read-all', 'markAllRead')->name('read-all');
        Route::put('/{notification}/read', 'markRead')->name('read');
    });
