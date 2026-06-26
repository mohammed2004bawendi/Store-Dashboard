<?php

namespace App\Domain\Products\Actions;

use App\Domain\Products\Data\CreateProductData;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class CreateProductAction
{
    public function execute(CreateProductData $data): Product
    {
        $product = Product::create($data->toArray());

        Cache::add('products_cache_version', 1);
        Cache::increment('products_cache_version');

        return $product;
    }
}
