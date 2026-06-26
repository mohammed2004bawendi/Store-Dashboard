<?php

namespace App\Domain\Customers\Actions;

use App\Models\Customer;
use Illuminate\Support\Facades\Cache;

class DeleteCustomerAction
{
    public function execute(Customer $customer): void
    {
        $customer->delete();

        Cache::add('customers_cache_version', 1);
        Cache::increment('customers_cache_version');
    }
}
