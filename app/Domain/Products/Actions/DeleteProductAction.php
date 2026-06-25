<?php

namespace App\Domain\Products\Actions;

use App\Models\Product;

class DeleteProductAction
{
    public function execute(Product $product): void
    {
        $product->delete();
    }
}
