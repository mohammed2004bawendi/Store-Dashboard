<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $product = Product::inRandomOrder()->first() ?? Product::factory()->create();
        $customer = Customer::inRandomOrder()->first() ?? Customer::factory()->create();
        $quantity = fake()->numberBetween(1, 5);
        $total = $product->price * $quantity;

        return [
            'customer_id' => $customer->id,
            'status' => fake()->randomElement(['جديد', 'قيد التجهيز', 'مكتمل']),
            'total_price' => 0, // مؤقتًا
        ];

    }
}
