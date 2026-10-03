<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use App\Models\Branch;
use Illuminate\Support\Facades\Route;

// Public auth endpoints, rate limited to slow down credential stuffing.
Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Branch names are needed on the public registration form.
Route::get('branches/options', fn () => Branch::orderBy('name')->get(['id', 'name']));

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('dashboard', DashboardController::class);

    // Read access for every signed-in user.
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('suppliers', SupplierController::class)->only(['index', 'show']);
    Route::apiResource('branches', BranchController::class)->only(['index', 'show']);

    Route::get('stock-levels', [StockController::class, 'levels']);
    Route::get('stock-movements', [StockController::class, 'movements']);
    Route::post('stock-movements', [StockController::class, 'recordMovement']);
    Route::post('stock-transfers', [StockController::class, 'transfer']);

    // Catalogue, branch and user management is admin only.
    Route::middleware('admin')->group(function () {
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
        Route::apiResource('suppliers', SupplierController::class)->except(['index', 'show']);
        Route::apiResource('branches', BranchController::class)->except(['index', 'show']);
        Route::apiResource('users', UserController::class);
    });
});
