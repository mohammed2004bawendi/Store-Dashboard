<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Data\CreateOrderData;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CreateOrderAction
{
    public function __construct(
        private readonly AddOrderItemsAction $addOrderItems,
    ) {
    }

    public function execute(CreateOrderData $data): Order
    {
        return DB::transaction(function () use ($data): Order {
            $customer = Customer::firstOrCreate(
                ['phone' => $data->phone],
                [
                    'name' => $data->name,
                    'address' => $data->address,
                ],
            );

            $order = Order::create([
                'customer_id' => $customer->id,
                'total_price' => 0,
                'status' => $data->status ?? 'processing',
            ]);

            $total = $this->addOrderItems->execute($order, $data->products);

            $order->update(['total_price' => $total]);

            Notification::send(
                User::where('role', 'logistics')->get(),
                new OrderCreatedNotification($order),
            );

            Cache::add('orders_cache_version', 1);
            Cache::increment('orders_cache_version');

            return $order;
        });
    }
}
