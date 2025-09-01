<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use App\Notifications\QuantityReminder;
use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class ProductController extends Controller
{
    use ApiResponseTrait, AuthorizesRequests;

    /**
     * Build products cache key with versioning (same pattern as OrderController).
     */
    private function productsCacheKey(Request $request): string
    {
        // Create/keep the cache version with initial value = 1
        $version = Cache::rememberForever('products_cache_version', fn () => 1);

        // Sort query parameters and exclude 'page' from hash for better cache hit ratio
        $params = $request->except('page');
        ksort($params);
        $hash = md5(json_encode($params));
        $page = (int) $request->get('page', 1);

        return "products.v{$version}.page.{$page}.{$hash}";
    }

    /**
     * Increment products cache version (old keys become obsolete automatically).
     */
    private function bumpProductsCacheVersion(): void
    {
        // If version key does not exist, create it and then increment
        Cache::add('products_cache_version', 1);
        Cache::increment('products_cache_version');
    }

    /**
     * List products with filters.
     */
    public function index(Request $request)
    {
        Gate::authorize('view-products');

        $key = $this->productsCacheKey($request);

        $products = Cache::remember($key, 60, function () use ($request) {
            $query = $this->applyFilters(Product::query(), $request);

            return $query->paginate();
        });

        return ProductResource::collection($products);
    }

    /**
     * Apply filters to product query.
     */
    private function applyFilters($query, Request $request)
    {
        return $query
            ->when($request->status, fn ($q, $val) => $q->where('status', $val))
            ->when($request->min_quantity, fn ($q, $val) => $q->where('quantity', '>=', $val))
            ->when($request->max_quantity, fn ($q, $val) => $q->where('quantity', '<=', $val))
            ->when($request->min_price, fn ($q, $val) => $q->where('price', '>=', $val))
            ->when($request->max_price, fn ($q, $val) => $q->where('price', '<=', $val))
            ->when($request->search, fn ($q, $val) => $q->where('name', 'like', "%{$val}%"));
    }

    /**
     * Create new product.
     */
    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $product = Product::create($request->validated());

        // Any product change should bump cache version
        $this->bumpProductsCacheVersion();

        return $this->success(['id' => $product->id], 'Product created successfully');
    }

    /**
     * Show single product.
     */
    public function show(Product $product)
    {
        $this->authorize('view', $product);

        return new ProductResource($product);
    }

    /**
     * Update product and notify if quantity is low.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        // // Send notification if stock is critically low
        // if ($product->quantity < 2 && $product->quantity >= 0) {
        //     $users = User::all();
        //     Notification::send($users, new QuantityReminder($product));
        //     // If users are large in number, use chunk() to avoid loading all users at once:
        //     // User::chunk(200, fn($batch) => Notification::send($batch, new QuantityReminder($product)));
        // }

        // Bump cache version instead of deleting cache entries
        $this->bumpProductsCacheVersion();

        return $this->success(['id' => $product->id], 'Product updated successfully');
    }

    /**
     * Delete product.
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        // Bump cache version after delete
        $this->bumpProductsCacheVersion();

        return $this->success([], 'Product deleted successfully');
    }

    /**
     * Count distinct customers who ordered this product.
     */
    public function customerCount(Product $product)
    {
        $this->authorize('view', $product);

        $count = $product->orders()
            ->distinct('customer_id')
            ->count('customer_id');

        return response()->json(['count' => $count]);
    }
}
