<?php

use App\Http\Controllers\Api\Common\PromotionController;
use App\Http\Controllers\Api\Manager\AccountController;
use App\Http\Controllers\Api\Manager\CustomerManagementController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Authenticate\AuthController;
use App\Http\Controllers\Api\Common\AdditionalInformationController;
use App\Http\Controllers\Api\Common\CategoryController;
use App\Http\Controllers\Api\Common\OrderController;
use App\Http\Controllers\Api\Manager\BannerController;
use App\Http\Controllers\Api\Manager\CategoryManagementController;
use App\Http\Controllers\Api\Manager\NotificationManagerController;
use App\Http\Controllers\Api\Common\NotificationController;
use App\Http\Controllers\Api\Common\ProductController;
use App\Http\Controllers\Api\Employee\EmployeeProfileController;
use App\Http\Controllers\Api\Customer\CustomerProfileController;
use App\Http\Controllers\Api\Employee\CustomerController;
use App\Http\Controllers\Api\Manager\ProductManagementController;
use App\Http\Controllers\Api\Manager\PromotionManagementController;
use App\Http\Controllers\Api\Manager\ReportManagementController;
use App\Http\Controllers\Api\Manager\CommissionManagementController;
use App\Models\Customer;
use App\Models\Notification;

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

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('signup', [AuthController::class, 'signup']);
    Route::post('delete-user', [AuthController::class, 'deleteAccount'])->middleware(['auth:sanctum', 'ability:customer']);
});

Route::prefix('manager')->middleware(['auth:sanctum', 'ability:admin'])->group(function () {
    Route::prefix('account')->group(function () {
        Route::post('create', [AccountController::class, 'create']);
        Route::get('list', [AccountController::class, 'list']);
        Route::get('{id}', [AccountController::class, 'detail']);
        Route::post('{id}/edit', [AccountController::class, 'edit']);
        Route::post('/{id}/delete', [AccountController::class, 'delete']);
    });
    Route::prefix('customer')->group(function () {
        Route::get('list', [CustomerManagementController::class, 'list']);
        Route::post('create', [CustomerManagementController::class, 'create']);
        Route::get('{id}', [CustomerManagementController::class, 'detail']);
        Route::post('/{id}/edit', [CustomerManagementController::class, 'update']);
        Route::post('/{id}/delete', [CustomerManagementController::class, 'delete']);
        Route::post('change-discount-rate', [CustomerManagementController::class, 'editDiscountRate']);
    });
    Route::prefix('category')->group(function () {
        Route::get('list', [CategoryManagementController::class, 'list']);
        Route::post('create', [CategoryManagementController::class, 'create']);
        Route::get('{id}', [CategoryManagementController::class, 'detail']);
        Route::post('/{id}/edit', [CategoryManagementController::class, 'update']);
        Route::post('/{id}/delete', [CategoryManagementController::class, 'delete']);
        Route::post('/{id}/disable', [CategoryManagementController::class, 'disable']);
    });
    Route::prefix('product')->group(function () {
        Route::get('list', [ProductManagementController::class, 'list']);
        Route::post('create', [ProductManagementController::class, 'create']);
        Route::get('{id}', [ProductManagementController::class, 'detail']);
        Route::post('/{id}/edit', [ProductManagementController::class, 'update']);
        Route::post('/{id}/delete', [ProductManagementController::class, 'delete']);
        Route::post('/{id}/disable', [ProductManagementController::class, 'disable']);
    });
    Route::prefix('banner')->group(function () {
        Route::get('list', [BannerController::class, 'list']);
        Route::post('create', [BannerController::class, 'create']);
        Route::post('reorder', [BannerController::class, 'reorder']);
        Route::post('/{id}/delete', [BannerController::class, 'delete']);
    });
    Route::prefix('promotion')->group(function () {
        Route::get('/', [PromotionManagementController::class, 'list']);
        Route::post('create', [PromotionManagementController::class, 'create']);
        Route::get('/{id}', [PromotionManagementController::class, 'detail']);
        Route::post('/{id}/delete', [PromotionManagementController::class, 'delete']);
        Route::post('/{id}/edit', [PromotionManagementController::class, 'edit']);
    });
    Route::prefix('notification')->group(function () {
        Route::get('list', [NotificationManagerController::class, 'list']);
        Route::post('create', [NotificationManagerController::class, 'create']);
        Route::post('/{id}/edit', [NotificationManagerController::class, 'edit']);
        Route::post('/{id}/delete', [NotificationManagerController::class, 'delete']);
    });
    Route::prefix('report')->group(function () {
        Route::get('/', [ReportManagementController::class, 'report']);
    });
    Route::prefix('commission')->group(function () {
        Route::get('by-employee', [CommissionManagementController::class, 'getEmployeesCommission']);
        Route::get('by-employee-specific-store', [CommissionManagementController::class, 'getEmployeeStoreCommission']);
    });
    Route::prefix('profile')->group(function () {
        Route::post("edit", [EmployeeProfileController::class, 'edit']);
    });
});
//Thông báo
Route::prefix('notification')->middleware(['auth:sanctum', 'ability:admin,customer,employee'])->group(function () {
    Route::get('get', [NotificationController::class, 'getList']);
    Route::post('seen', [NotificationController::class, 'seenAction']);
    Route::post('seen-all', [NotificationController::class, 'seenAllAction']);
    Route::post('delete', [NotificationController::class, 'deleteAction']);
    Route::post('delete-all', [NotificationController::class, 'deleteAllAction']);
});
//Group quản lý profile
Route::prefix('employee')->middleware(['auth:sanctum', 'ability:employee'])->group(function () {
    Route::prefix('profile')->group(function () {
        Route::post("edit", [EmployeeProfileController::class, 'edit']);
        Route::post("change-password", [EmployeeProfileController::class, 'changePassword']);
    });
    Route::prefix('customer')->group(function () {
        Route::get('list-managed', [CustomerController::class, 'listManagedByMe']);
        Route::post('create', [CustomerController::class, 'create']);
        Route::get('{id}', [CustomerController::class, 'detail']);
        Route::post('{id}/edit', [CustomerController::class, 'edit']);
        Route::post('{id}/delete', [CustomerController::class, 'delete']);
    });
});
Route::prefix('customer')->middleware(['auth:sanctum', 'ability:customer'])->group(function () {
    Route::prefix('profile')->group(function () {
        Route::post("edit", [CustomerProfileController::class, 'edit']);
        Route::post("change-password", [CustomerProfileController::class, 'changePassword']);
    });
});
Route::prefix('customer')->group(function () {
    Route::get('/list', [CategoryController::class, 'list']);
});
//Group common
Route::prefix('product')->group(function () {
    Route::get('/list', [ProductController::class, 'list']);
    Route::get('/list-for-manager', [ProductController::class, 'listForManager']);
    Route::get('/{id}', [ProductController::class, 'detail']);
});
Route::prefix('category')->group(function () {
    Route::get('/list', [CategoryController::class, 'list']);
    Route::get('/list-for-manager', [CategoryController::class, 'list']);
    Route::get('/{id}', [CategoryController::class, 'detail']);
});
Route::prefix('order')->middleware(['auth:sanctum', 'ability:admin,customer,employee'])->group(function () {
    Route::post('calculate', [OrderController::class, 'calculateOrder']);
    Route::post('checkout', [OrderController::class, 'checkout']);
    Route::get('list', [OrderController::class, 'list']);
    Route::post('change_status', [OrderController::class, 'changeMultipleStatus']);
    Route::get('{id}', [OrderController::class, 'detail']);
    Route::post('{id}/change_status', [OrderController::class, 'changeStatus']);
    Route::post('{id}/edit', [OrderController::class, 'edit']);
    Route::post('delete', [OrderController::class, 'delete'])->middleware(['auth:sanctum', 'ability:admin']);
});
//Common
Route::prefix('common')->group(function () {
    Route::get('category', [CategoryManagementController::class, 'listAll']);
    Route::get('product-list', [ProductManagementController::class, 'list']);
    Route::get('category-with-product', [CategoryManagementController::class, 'listAllWithProduct']);
    Route::get('banners', [BannerController::class, 'list']);
    Route::get('discount-rate', [CustomerManagementController::class, 'getDiscountRate']);
    Route::get('provinces', [AdditionalInformationController::class, 'getProvinces']);
    Route::middleware(['auth:sanctum', 'ability:admin,customer,employee'])->group(function () {
        Route::get('customer', [CustomerManagementController::class, 'listAll']);
        Route::get('user-info', [AuthController::class, 'info']);
        Route::get('employee', [AccountController::class, 'listAllEmployee']);
        Route::get('promotion', [PromotionController::class, 'getUserPromotion']);
    });
});
