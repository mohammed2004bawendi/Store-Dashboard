<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;
use App\Models\Product;

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
            'total_price' => 0 // مؤقتًا
];

    }
}
