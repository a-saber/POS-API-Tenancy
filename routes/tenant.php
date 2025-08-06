<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Example route to test tenant context get all users but with access token

    Route::middleware('auth:sanctum')->get('/users', function () {
    return User::all();
});
    // Route::get('/users', function () {
    //     return User::all();
    // });
    Route::get('/', function () {
        return response()->json([
            'message' => 'Welcome to the tenant application API',
            'tenant_id' => tenant('id'),
        ]);
    });
});
