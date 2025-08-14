<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\quantityReminder;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;


class OrderService 
{
    use Notifiable;

    // Create a new order with customer and products
    public function create(array $data): Order
    {
        
       $order =  DB::transaction(function () use ($data): Order {
            $customer = $this->createOrGetCustomer($data);

            $order = Order::create([
                'customer_id' => $customer->id,
                'total_price' => 0,
                'status' => $data['status'] ?? 'processing'
            ]);

            $total = $this->attachProductsAndCalculateTotal($order, $data['products']);

            $order->update(['total_price' => $total]);

            return $order;

        });

        return $order;
        

    }

    // Update an existing order and optionally customer/products
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


    // private function sendNotifications(Order $order)
    // {
    
    //  $users = User::where('role', 'logistics')->get();
    //  Notification::send(notifiables: $users, notification: new OrderCreatedNotification($order));

    // }



    // Create or fetch existing customer by phone
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


    // Attach products to order and calculate total
    private function attachProductsAndCalculateTotal(Order $order, array $products): float
    {
        $total = 0;

        foreach ($products as $item) {
            $product = Product::findOrFail($item['id']);
            $quantity = $item['quantity'];

            // Check stock
            if ($product->quantity < $quantity) {
                throw new Exception("الكمية المطلوبة من المنتج {$product->name} غير متوفرة.");
            }

            // Attach product to order with pivot data
            $order->products()->attach($product->id, [
                'quantity' => $quantity,
                'price' => $product->price
            ]);

            // Decrease stock
            $product->decrement('quantity', $quantity);

            // Notify if stock is low
            if ($product->quantity < 2 && $product->quantity >= 0) {
                $users = User::all();
                Notification::send($users, new quantityReminder($product));
            }

            $total += $quantity * $product->price;
        }

        return $total;
    }

    // Restore quantities when removing products from order
    private function restoreProductQuantities(Order $order): void
    {
        foreach ($order->products as $product) {
            $product->increment('quantity', $product->pivot->quantity);
        }
    }

    // Update customer information
    private function updateCustomerInfo(Customer $customer, array $data): void
    {
        $customer->update([
            'name'    => $data['name'] ?? $customer->name,
            'address' => $data['address'] ?? $customer->address,
        ]);
    }
}
