<?php

namespace App\Domain\Orders\Actions;

use App\Models\Order;

class DeleteOrderAction
{
    public function execute(Order $order): void
    {
        foreach ($order->products as $product) {
            $product->increment('quantity', $product->pivot->quantity);
        }

        $order->products()->detach();

        $order->delete();
    }
}
