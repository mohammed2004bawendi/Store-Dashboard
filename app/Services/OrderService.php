<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\quantityReminder;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;



class OrderService
{

        use Notifiable;

    public function create(array $data): void
    {
        DB::transaction(function () use ($data) {
            $customer = $this->createOrGetCustomer($data);

            $order = Order::create([
                'customer_id' => $customer->id,
                'total_price' => 0,
                'status' => $data['status'] ?? 'processing'
            ]);

            $total = $this->attachProductsAndCalculateTotal($order, $data['products']);

            $order->update(['total_price' => $total]);
        });
    }

    public function update(array $data, Customer $customer, Order $order): void
    {
        DB::transaction(function () use ($data, $customer, $order) {
            if (!empty($data['products'])) {
                $this->restoreProductQuantities($order);
                $order->products()->detach();
            }

            if (!empty($data['customer'])) {
                $this->updateCustomerInfo($customer, $data['customer']);
            }

            $order->update([
                'status' => $data['status'] ?? $order->status,
                'total_price' => $data['total_price'] ?? $order->total_price,
            ]);

            if (!empty($data['products'])) {
                $total = $this->attachProductsAndCalculateTotal($order, $data['products']);
                $order->update(['total_price' => $total]);
            }
        });
    }

    private function createOrGetCustomer(array $data): Customer
    {
        return Customer::firstOrCreate(
            ['phone' => $data['phone']],
            [
                'name' => $data['name'],
                'address' => $data['address']
            ]
        );
    }

    private function attachProductsAndCalculateTotal(Order $order, array $products): float
    {
        $total = 0;

        foreach ($products as $item) {
            $product = Product::findOrFail($item['id']);
            $quantity = $item['quantity'];

            if ($product->quantity < $quantity) {
                throw new Exception("الكمية المطلوبة من المنتج {$product->name} غير متوفرة.");
            }

            if ($product->quantity < 2) {
                $users = User::all();
                 Notification::send($users, new quantityReminder($product));
            }

            $order->products()->attach($product->id, [
                'quantity' => $quantity,
                'price' => $product->price
            ]);

            $product->decrement('quantity', $quantity);
            $total += $quantity * $product->price;
        }

        return $total;
    }

    private function restoreProductQuantities(Order $order): void
    {
        foreach ($order->products as $product) {
            $product->increment('quantity', $product->pivot->quantity);
        }
    }

    private function updateCustomerInfo(Customer $customer, array $data): void
    {
        $customer->update([
            'name'    => $data['name'] ?? $customer->name,
            'address' => $data['address'] ?? $customer->address,
        ]);
    }
}
