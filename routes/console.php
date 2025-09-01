<?php

use App\Models\Product;
use App\Models\User;
use App\Notifications\QuantityReminder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    // Fetch products with low stock (quantity < 2 but not negative)
    Product::where('quantity', '<', 2)
        ->where('quantity', '>=', 0)
        ->orderBy('id')
        ->chunkById(200, function ($products) {
            // Select target users (adjust filtering based on roles if needed)
            $users = User::all();

            foreach ($products as $product) {
                // Prevent duplicate notifications for the same product within 15 minutes
                $key = "low-stock:product:{$product->id}";
                if (! Cache::has($key)) {
                    Notification::send($users, new QuantityReminder($product));
                    Cache::put($key, true, now()->addMinutes(15));
                }
            }
        });
})->everyThirtyMinutes();

Schedule::call(function () {
    // Clear all cache every 6 hours
    Cache::flush();
})->everySixHours();
