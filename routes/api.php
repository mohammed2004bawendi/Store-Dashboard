<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuantityReminderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // Customers & Orders
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('customers.orders', OrderController::class);

    // Products
    Route::apiResource('products', ProductController::class);
    Route::get('/products/{product}/buyers-count', [ProductController::class, 'customerCount']);
    // Route::post('/products/weo', [ProductController::class, 'withCache']);

    // Orders (Global)
    Route::get('/orders', [OrderController::class, 'indexall']);
    Route::get('/orders/{order}', [OrderController::class, 'showOne']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);

    // Custom
    Route::get('/orders/export', [OrderController::class, 'export']);
    Route::get('/orders/{order}/invoice', [OrderController::class, 'downloadInvoice'])->name('orders.invoice');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'data']);

    // Notifications

    Route::get('/notifications', [QuantityReminderController::class, 'index']);
    Route::post('/notifications/{id}/read', [QuantityReminderController::class, 'markAsRead']);
});
