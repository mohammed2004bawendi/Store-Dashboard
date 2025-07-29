<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
        use App\Traits\ApiResponseTrait;


class ProductController extends Controller
{

             use ApiResponseTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        Gate::authorize('view-products');

        $query = Product::query();

        $query->when($request->status, fn($q, $status) => $q->where('status', $status));
        $query->when($request->min_quantity, fn($q, $min) => $q->where('quantity', '>=', $min));
        $query->when($request->max_quantity, fn($q, $max) => $q->where('quantity', '<=', $max));
        $query->when($request->min_price, fn($q, $min) => $q->where('price', '>=', $min));
        $query->when($request->max_price, fn($q, $max) => $q->where('price', '<=', $max));
        $query->when($request->search, fn($q, $search) => $q->where('name', 'like', '%'. $search . '%' ));

        return ProductResource::collection($query->paginate());
    }

    /**
     * Show the form for creating a new resource.
     */



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Product::class);

        $data = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'price' => 'required|integer',
            'quantity' => 'required|integer',
            'status' => 'required'
        ]);

        $product = Product::create($data);


        return $this->success([], 'تم إنشاء المنتج بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        Gate::authorize('view', $product);
        return new ProductResource($product);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        Gate::authorize('update', $product);

        $product->update([
             ...$request->validate([
                'name' => 'required',
                'description' => 'required',
                'price' => 'required|integer',
                'quantity' => 'required|integer',
                'status' => 'required'
            ])
            ]);

        return $this->success([], 'تم نحديث المنتج بنجاح');    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        Gate::authorize('delete', $product);
        $product->delete();
        return $this->success([], 'تم حذف المنتج بنجاح');    }


    public function buyersCount(Product $product)
{
    Gate::authorize('view', $product);

    $customerCount = $product->orders()
        ->distinct('customer_id')
        ->count('customer_id');

    return response()->json(['count' => $customerCount]);
}

}
