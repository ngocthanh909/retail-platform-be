<?php

use App\Http\Controllers\Api\Manager\AccountController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Authenticate\AuthController;

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
});
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});
