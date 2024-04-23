<?php

use App\Http\Controllers\Api\Manager\AccountController;
use App\Http\Controllers\Api\Manager\CustomerManagementController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Authenticate\AuthController;
use App\Http\Controllers\Api\Manager\CategoryManagementController;
use App\Http\Controllers\Api\Manager\ProductManagementController;

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

// Route::get("users", [UserController::class, "getUser"]);

Route::prefix('manager')->middleware(['auth:sanctum', 'ability:admin'])->group(function () {
    Route::prefix('account')->group(function(){
        Route::post('create', [AccountController::class, 'createUser']);
    });
    Route::prefix('customer')->group(function(){
        Route::get('list', [CustomerManagementController::class, 'list']);
        Route::post('create', [CustomerManagementController::class, 'create']);
        Route::post('/{id}/edit', [CustomerManagementController::class, 'update']);
        Route::post('/{id}/delete', [CustomerManagementController::class, 'delete']);
    });
    Route::prefix('category')->group(function(){
        Route::get('list', [CategoryManagementController::class, 'list']);
        Route::post('create', [CategoryManagementController::class, 'create']);
        Route::get('{id}', [CategoryManagementController::class, 'detail']);
        Route::post('/{id}/edit', [CategoryManagementController::class, 'update']);
        Route::post('/{id}/delete', [CategoryManagementController::class, 'delete']);
    });
    Route::prefix('product')->group(function(){
        Route::get('list', [ProductManagementController::class, 'list']);
        Route::post('create', [ProductManagementController::class, 'create']);
        Route::post('/{id}/edit', [ProductManagementController::class, 'update']);
        Route::post('/{id}/delete', [ProductManagementController::class, 'delete']);
    });
    Route::prefix('common')->group(function(){
        Route::get('category', [CategoryManagementController::class, 'list']);
        Route::get('discount-rate', [CustomerManagementController::class, 'getDiscountRate']);
        Route::post('change-discount-rate', [CustomerManagementController::class, 'editDiscountRate']);
    });
});
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('signup', [AuthController::class, 'signup']);
});
