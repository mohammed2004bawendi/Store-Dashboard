<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Data\UpdateOrderData;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class UpdateOrderAction
{
    public function __construct(
        private readonly AddOrderItemsAction $addOrderItems,
    ) {
    }

    public function execute(Order $order, UpdateOrderData $data): void
    {
        DB::transaction(function () use ($order, $data) {
            if ($data->hasProducts()) {
                $this->restoreProductQuantities($order);
                $order->products()->detach();
            }

            if ($data->hasCustomer() && $order->customer) {
                $order->customer->update([
                    'name' => $data->customer['name'] ?? $order->customer->name,
                    'address' => $data->customer['address'] ?? $order->customer->address,
                ]);
            }

            $order->update([
                'status' => $data->status ?? $order->status,
                'total_price' => $data->hasTotalPrice() ? $data->totalPrice : $order->total_price,
            ]);

            if ($data->hasProducts()) {
                $total = $this->addOrderItems->execute($order, $data->products);
                $order->update(['total_price' => $total]);
            }

            DB::table('cache')
                ->where('key', 'like', 'laravel_cache_orders.page.%')
                ->delete();
        });
    }

    private function restoreProductQuantities(Order $order): void
    {
        foreach ($order->products as $product) {
            $product->increment('quantity', $product->pivot->quantity);
        }
    }
}
