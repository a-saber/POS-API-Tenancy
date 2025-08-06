<?php

use Illuminate\Support\Facades\Route;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        // register
        Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);
        // login
        Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);
        // your actual routes
        Route::get('/', function () {
            return response()->json([
                'message' => 'Welcome to the central application API',
            ]);
        });
    });
}
