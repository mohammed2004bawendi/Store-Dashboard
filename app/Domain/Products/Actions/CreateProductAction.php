<?php

namespace App\Domain\Products\Actions;

use App\Domain\Products\Data\CreateProductData;
use App\Models\Product;

class CreateProductAction
{
    public function execute(CreateProductData $data): Product
    {
        return Product::create($data->toArray());
    }
}
