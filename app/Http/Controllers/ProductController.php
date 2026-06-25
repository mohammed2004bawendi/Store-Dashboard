<?php

namespace App\Http\Controllers;

use App\Domain\Products\Actions\CountProductCustomersAction;
use App\Domain\Products\Actions\CreateProductAction;
use App\Domain\Products\Actions\DeleteProductAction;
use App\Domain\Products\Actions\ListProductsAction;
use App\Domain\Products\Actions\UpdateProductAction;
use App\Domain\Products\Data\CreateProductData;
use App\Domain\Products\Data\ProductFiltersData;
use App\Domain\Products\Data\UpdateProductData;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    use AuthorizesRequests, ApiResponseTrait;

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

        return $this->success(['id' => $product->id], "\u{062A}\u{0645} \u{0625}\u{0646}\u{0634}\u{0627}\u{0621} \u{0627}\u{0644}\u{0645}\u{0646}\u{062A}\u{062C} \u{0628}\u{0646}\u{062C}\u{0627}\u{062D}");
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

        return $this->success(['id' => $product->id], "\u{062A}\u{0645} \u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0645}\u{0646}\u{062A}\u{062C} \u{0628}\u{0646}\u{062C}\u{0627}\u{062D}");
    }

    public function destroy(Product $product, DeleteProductAction $deleteProduct)
    {
        $this->authorize('delete', $product);

        $deleteProduct->execute($product);

        return $this->success([], "\u{062A}\u{0645} \u{062D}\u{0630}\u{0641} \u{0627}\u{0644}\u{0645}\u{0646}\u{062A}\u{062C} \u{0628}\u{0646}\u{062C}\u{0627}\u{062D}");
    }

    public function customerCount(Product $product, CountProductCustomersAction $countProductCustomers)
    {
        $this->authorize('view', $product);

        return response()->json([
            'count' => $countProductCustomers->execute($product),
        ]);
    }
}
