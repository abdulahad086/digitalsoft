<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/dashboard', [DashboardController::class, 'show']);

        // All authenticated users can read branches & products (needed for order/inventory UI)
        Route::apiResource('branches', BranchController::class)->only(['index', 'show']);
        Route::apiResource('products', ProductController::class)->only(['index', 'show']);

        // Super Admin: manage branches + products
        Route::middleware(['role:Super Admin'])->group(function () {
            Route::apiResource('branches', BranchController::class)->except(['index', 'show']);
            Route::apiResource('products', ProductController::class)->except(['index', 'show']);
        });

        // Super Admin + Branch Manager: manage inventory, view reports
        Route::middleware(['role:Super Admin,Branch Manager'])->group(function () {
            Route::get('/inventory', [InventoryController::class, 'index']);
            Route::get('/inventory/movements', [InventoryController::class, 'movements']);
            Route::post('/inventory/add-stock', [InventoryController::class, 'addStock']);
            Route::post('/inventory/adjust-stock', [InventoryController::class, 'adjustStock']);
            Route::post('/inventory/transfer-stock', [InventoryController::class, 'transferStock']);
            Route::get('/reports', [DashboardController::class, 'reports']);
        });

        // Branch Manager + Sales User: create and view orders
        Route::middleware(['role:Branch Manager,Sales User'])->group(function () {
            Route::post('/orders', [OrderController::class, 'store']);
            Route::get('/orders', [OrderController::class, 'index']);
            Route::get('/orders/{order}', [OrderController::class, 'show']);
        });
    });
});

