<?php

namespace App\Domain\Products\Actions;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class DeleteProductAction
{
    public function execute(Product $product): void
    {
        $product->delete();

        Cache::add('products_cache_version', 1);
        Cache::increment('products_cache_version');
    }
}
