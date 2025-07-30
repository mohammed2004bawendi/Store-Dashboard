<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Order;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
            protected $policies = [
    \App\Models\Product::class => \App\Policies\ProductPolicy::class,
];
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('view-products', fn($user) => in_array($user->role,['admin', 'product_manager', 'support']));
        Gate::define('view-orders', fn($user) => in_array($user->role, ['admin', 'support', 'product_manager']));
        Gate::define('view-customers', fn($user) => in_array($user->role, ['admin', 'product_manager', 'support']));
        Gate::define('view-dashboard', fn($user) => in_array($user->role, ['admin', 'product_manager', 'support']));


    }
}


