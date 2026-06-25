<?php

namespace App\Domain\Products\Actions;

use App\Domain\Products\Data\UpdateProductData;
use App\Models\Product;
use App\Models\User;
use App\Notifications\quantityReminder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class UpdateProductAction
{
    public function execute(Product $product, UpdateProductData $data): Product
    {
        $product->update($data->toArray());

        if ($product->quantity < 2 && $product->quantity >= 0) {
            Notification::send(User::all(), new quantityReminder($product));
        }

        DB::table('cache')
            ->where('key', 'like', 'laravel_cache_products.page.%')
            ->delete();

        return $product;
    }
}
