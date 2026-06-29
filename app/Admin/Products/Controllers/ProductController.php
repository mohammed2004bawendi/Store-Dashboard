<?php

namespace App\Admin\Products\Controllers;

use App\Admin\Products\Requests\StoreProductRequest;
use App\Admin\Products\Requests\UpdateProductRequest;
use App\Admin\Products\Resources\ProductResource;
use App\Domain\Products\Actions\CountProductCustomersAction;
use App\Domain\Products\Actions\CreateProductAction;
use App\Domain\Products\Actions\DeleteProductAction;
use App\Domain\Products\Actions\ListProductsAction;
use App\Domain\Products\Actions\UpdateProductAction;
use App\Domain\Products\Data\CreateProductData;
use App\Domain\Products\Data\ProductFiltersData;
use App\Domain\Products\Data\UpdateProductData;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Http\ApiResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    use ApiResponseTrait, AuthorizesRequests;

    public function index(Request $request, ListProductsAction $listProducts)
    {
        Gate::authorize('view-products');

        $products = $listProducts->execute(
            ProductFiltersData::fromArray($request->all())
        );

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request, CreateProductAction $createProduct)
    {
        $this->authorize('create', Product::class);

        $product = $createProduct->execute(
            CreateProductData::fromArray($request->validated())
        );

        return $this->success(['id' => $product->id], 'Product created successfully');
    }

    public function show(Product $product)
    {
        $this->authorize('view', $product);

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $updateProduct)
    {
        $this->authorize('update', $product);

        $product = $updateProduct->execute(
            $product,
            UpdateProductData::fromArray($request->validated())
        );

        return $this->success(['id' => $product->id], 'Product updated successfully');
    }

    public function destroy(Product $product, DeleteProductAction $deleteProduct)
    {
        $this->authorize('delete', $product);

        $deleteProduct->execute($product);

        return $this->success([], 'Product deleted successfully');
    }

    public function customerCount(Product $product, CountProductCustomersAction $countProductCustomers)
    {
        $this->authorize('view', $product);

        return response()->json([
            'count' => $countProductCustomers->execute($product),
        ]);
    }
}
