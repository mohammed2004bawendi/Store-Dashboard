<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Data\CreateOrderData;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CreateOrderAction
{
    public function __construct(
        private readonly AddOrderItemsAction $addOrderItems,
    ) {
    }

    public function execute(CreateOrderData $data): void
    {
        DB::transaction(function () use ($data) {
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
        });
    }
}
