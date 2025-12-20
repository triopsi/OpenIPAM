<?php

use App\Http\Controllers\Api\V1\ApiTokenController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\IpAddressController;
use App\Http\Controllers\Api\V1\IpAddressGroupController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    // Public authentication routes
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Authentication
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);

        // API Token Management
        Route::get('/tokens', [ApiTokenController::class, 'index']);
        Route::post('/tokens', [ApiTokenController::class, 'store']);
        Route::delete('/tokens/{token}', [ApiTokenController::class, 'destroy']);

        // Users (admin only)
        Route::apiResource('users', UserController::class);

        // Devices
        Route::apiResource('devices', DeviceController::class);
        Route::post('/devices/{device}/assign-ip', [DeviceController::class, 'assignIp']);
        Route::delete('/devices/{device}/unassign-ip/{ipAddress}', [DeviceController::class, 'unassignIp']);

        // IP Addresses - Custom routes MUST come before apiResource
        Route::post('/ip-addresses/bulk-create', [IpAddressController::class, 'bulkCreate']);
        Route::put('/ip-addresses/bulk-update', [IpAddressController::class, 'bulkUpdate']);
        Route::apiResource('ip-addresses', IpAddressController::class);

        // IP Address Groups
        Route::apiResource('ip-address-groups', IpAddressGroupController::class);
    });
});
