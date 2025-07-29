<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DashboardController;

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
        'role' => $user->role
    ]);
});


    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('customers.orders', OrderController::class);
    Route::apiResource('products', ProductController::class);

    Route::get('/products/{product}/buyers-count', [ProductController::class, 'buyersCount']);
    Route::get('/dashboard', [DashboardController::class, 'data']);

    Route::get('/orders/export', [OrderController::class, 'export']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'indexall']);
    Route::get('/orders/{order}', [OrderController::class, 'showOne']);
    Route::get('/orders/{order}/invoice', [OrderController::class, 'downloadInvoice'])->name('orders.invoice');

    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);



});


