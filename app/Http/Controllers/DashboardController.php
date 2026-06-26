<?php

// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    // Show dashboard view
    public function index()
    {
        return view('dashboard');
    }

    // Return dashboard stats as JSON
    public function data()
    {
        Gate::authorize('view-dashboard');

        return response()->json([
            // Total counts
            'customersCount' => Customer::count(),
            'productsCount' => Product::count(),
            'ordersCount' => Order::count(),

            // Monthly order count
            'monthlyOrders' => DB::table('orders')
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
                ->groupBy('month')
                ->orderBy('month')
                ->get(),

            // Monthly sales total
            'monthlySales' => Order::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total_price) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
        ]);
    }
}
