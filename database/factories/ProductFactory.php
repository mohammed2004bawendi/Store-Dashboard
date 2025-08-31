<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(50, 500),
            'quantity' => fake()->numberBetween(1, 20),
            'status' => fake()->randomElement(['متوفر', 'غير متوفر']),
        ];
    }
}
