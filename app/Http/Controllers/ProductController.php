<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Notification;
use App\Notifications\quantityReminder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Notifications\ProductAlmostOut;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;



class ProductController extends Controller
{
    use AuthorizesRequests, ApiResponseTrait;

    // List products with filters
    public function index(Request $request)
    {
        Gate::authorize('view-products');

        $key = 'products.page.' . $request->get('page', 1) . '.' . md5(json_encode($request->all()));

        $products = Cache::remember($key, 60, function () use ($request) {
            $query = $this->applyFilters(Product::query(), $request);
            return $query->paginate();
        });

        return ProductResource::collection($products);
    }

    // Apply filters to product query
    private function applyFilters($query, Request $request)
    {
        return $query
            ->when($request->status, fn($q, $val) => $q->where('status', $val))
            ->when($request->min_quantity, fn($q, $val) => $q->where('quantity', '>=', $val))
            ->when($request->max_quantity, fn($q, $val) => $q->where('quantity', '<=', $val))
            ->when($request->min_price, fn($q, $val) => $q->where('price', '>=', $val))
            ->when($request->max_price, fn($q, $val) => $q->where('price', '<=', $val))
            ->when($request->search, fn($q, $val) => $q->where('name', 'like', "%$val%"));
    }





    // Create new product
    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $product = Product::create($request->validated());

        return $this->success(['id' => $product->id], 'تم إنشاء المنتج بنجاح');
    }

    // Show single product
    public function show(Product $product)
    {
        $this->authorize('view', $product);

        return new ProductResource($product);
    }

    // Update product and notify if quantity is low
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        // Notify users if quantity is less than 2
        if ($product->quantity < 2 && $product->quantity >= 0) {
            $users = User::all();
            Notification::send($users, new quantityReminder($product));
        }

       
         // Clear all product-related cached pages
        DB::table('cache')
            ->where('key', 'like', 'laravel_cache_products.page.%')
            ->delete();

        return $this->success(['id' => $product->id], 'تم تحديث المنتج بنجاح');
    }

    // Delete product
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return $this->success([], 'تم حذف المنتج بنجاح');
    }

    // Count distinct customers who ordered this product
    public function customerCount(Product $product)
    {
        $this->authorize('view', $product);

        $count = $product->orders()
            ->distinct('customer_id')
            ->count('customer_id');

        return response()->json(['count' => $count]);
    }
}
