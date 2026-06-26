<?php

namespace App\Domain\Products\Actions;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DeleteProductAction
{
    public function execute(Product $product): void
    {
        $product->delete();

        DB::table('cache')
            ->where('key', 'like', 'laravel_cache_products.page.%')
            ->delete();
    }
}
