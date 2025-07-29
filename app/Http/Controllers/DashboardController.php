<?php

// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;



use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
public function index()
{
    return view('dashboard');
}


public function data()
{

        Gate::authorize('view-dashboard');

    return response()->json([
        'customersCount' => Customer::count(),
        'productsCount' => Product::count(),
        'ordersCount' => Order::count(),
        'monthlyOrders' => DB::table('orders')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get(),
        'monthlySales' => Order::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total_price) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get(),
    ]);
}


}

