<?php

namespace App\Domain\Products\Actions;

use App\Models\Product;

class CountProductCustomersAction
{
    public function execute(Product $product): int
    {
        return $product->orders()
            ->distinct('customer_id')
            ->count('customer_id');
    }
}
