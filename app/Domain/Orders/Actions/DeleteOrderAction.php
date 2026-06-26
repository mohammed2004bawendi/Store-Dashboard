<?php

namespace App\Domain\Orders\Actions;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;

class DeleteOrderAction
{
    public function execute(Order $order): void
    {
        foreach ($order->products as $product) {
            $product->increment('quantity', $product->pivot->quantity);
        }

        $order->products()->detach();

        $order->delete();

        Cache::add('orders_cache_version', 1);
        Cache::increment('orders_cache_version');
    }
}
