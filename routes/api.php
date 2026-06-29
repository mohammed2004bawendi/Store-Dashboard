<?php

use App\Admin\Ai\Controllers\AiAssistantController;
use App\Admin\Auth\Controllers\AuthController;
use App\Admin\Customers\Controllers\CustomerController;
use App\Admin\Dashboard\Controllers\DashboardController;
use App\Admin\Notifications\Controllers\QuantityReminderController;
use App\Admin\Orders\Controllers\OrderController;
use App\Admin\Products\Controllers\ProductController;
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

    Route::post('/ai/assistant', AiAssistantController::class);

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
