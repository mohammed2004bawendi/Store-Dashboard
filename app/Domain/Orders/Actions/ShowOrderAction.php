<?php

namespace App\Domain\Orders\Actions;

use App\Models\Order;

class ShowOrderAction
{
    public function execute(Order $order): Order
    {
        return $order->load(['products', 'customer']);
    }
}
