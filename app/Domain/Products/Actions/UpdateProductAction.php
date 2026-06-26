<?php

namespace App\Domain\Products\Actions;

use App\Domain\Products\Data\UpdateProductData;
use App\Models\Product;
use App\Models\User;
use App\Notifications\quantityReminder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class UpdateProductAction
{
    public function execute(Product $product, UpdateProductData $data): Product
    {
        $product->update($data->toArray());

        if ($product->quantity < 2 && $product->quantity >= 0) {
            Notification::send(User::all(), new quantityReminder($product));
        }

        Cache::add('products_cache_version', 1);
        Cache::increment('products_cache_version');

        return $product;
    }
}
