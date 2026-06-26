<?php

namespace App\Domain\Orders\Actions;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DeleteOrderAction
{
    public function execute(Order $order): void
    {
        foreach ($order->products as $product) {
            $product->increment('quantity', $product->pivot->quantity);
        }

        $order->products()->detach();

        $order->delete();

        DB::table('cache')
            ->where('key', 'like', 'laravel_cache_orders.page.%')
            ->delete();
    }
}
