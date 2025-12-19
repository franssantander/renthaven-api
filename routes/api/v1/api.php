<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/login', function () {
        return 'hello';
    })->middleware('auth:api');

    Route::get('/health', function () {
        return response()->json(['status' => 'ok']);
    });
});
