<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::where('status', 'متوفر')->get();

        foreach ($customers as $customer) {
            // أنشئ طلب جديد للعميل
            $order = Order::create([
                'customer_id' => $customer->id,
                'status' => 'قيد التنفيذ',
                'total_price' => 0, // مؤقتاً
            ]);

            $total = 0;

            // اختر منتجات عشوائية
            $chosenProducts = $products->random(rand(1, 4));

            foreach ($chosenProducts as $product) {
                $quantity = rand(1, 3);
                $price = $product->price;
                $subtotal = $price * $quantity;

                // اربط المنتج بالطلب في جدول pivot
                $order->products()->attach($product->id, [
                    'quantity' => $quantity,
                    'price' => $price,
                ]);

                // تحديث السعر الإجمالي
                $total += $subtotal;

                // إنقاص الكمية من المنتج
                $product->decrement('quantity', $quantity);
            }

            // تحديث الطلب بالسعر الإجمالي
            $order->update(['total_price' => $total]);
        }
    }
}
