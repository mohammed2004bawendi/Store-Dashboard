<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('login');
});

Route::get('/store', function () {
    return view('store');
});

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::get('/profile', function () {
    return view('profile');
});

Route::get('/products', function () {
    return view('products.products');
});

Route::get('/products/{id}', function ($id) {
    return view('products.show');
})->where('id', '[0-9]+')->name('products.show');

Route::get('/customers', function () {
    return view('Customers.index');
});
Route::get('/customers/{id}', function () {
    return view('Customers.show');
});

Route::get('/orders', function () {
    return view('Orders.index');
});

Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export');
Route::get('/orders/{order}', [OrderController::class, 'showPage'])->name('orders.show');
