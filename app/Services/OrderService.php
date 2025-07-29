<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{
    public function create(array $data): void
    {
DB::transaction(function () use ($data) {
        $customer = Customer::firstOrCreate(
            ['phone' => $data['phone']],
            [
                'name' => $data['name'],
                'address' => $data['address']
            ]
        );

        $order = Order::create([
            'customer_id' => $customer->id,
            'total_price' => 0,
            'status' => $data['status'] ?? 'processing'
        ]);

        $total = 0;

        foreach ($data['products'] as $item) {
            $product = Product::findOrFail($item['id']);
            $quantity = $item['quantity'];

            if ($product->quantity < $quantity) {
                throw new \Exception("الكمية المطلوبة من المنتج {$product->name} غير متوفرة.");
            }

            $order->products()->attach($product->id, [
                'quantity' => $quantity,
                'price' => $product->price
            ]);

            $total += $quantity * $product->price;

            $product->decrement('quantity', $quantity);
        }

        $order->update(['total_price' => $total]);
    });    }


    public function update(array $data, $customer, $order) {
    DB::transaction(function () use ($data, $customer, $order) {
        if (!empty($data['products'])) {
            foreach ($order->products as $product) {
                $product->increment('quantity', $product->pivot->quantity);
            }

            $order->products()->detach();
        }

                if (!empty($data['customer'])) {

            $customer->update([
                'name'    => $data['customer']['name'] ?? $customer->name,
                'address' => $data['customer']['address'] ?? $customer->address,
            ]);
        }


        $order->update([
            'status' => $data['status'] ?? $order->status,
            'total_price' => $data['total_price'] ?? $order->total_price,
        ]);

        $total = 0;

        if (!empty($data['products'])) {
            foreach ($data['products'] as $item) {
                $product = Product::findOrFail($item['id']);
                $qty = $item['quantity'];

                if ($product->quantity < $qty) {
                    throw new \Exception("الكمية المطلوبة من المنتج {$product->name} غير متوفرة.");
                }

                $order->products()->attach($product->id, [
                    'quantity' => $qty,
                    'price' => $product->price,
                ]);

                $product->decrement('quantity', $qty);
                $total += $qty * $product->price;
            }

            $order->update([
                'total_price' => $total,
            ]);
        }
    });
}

}
