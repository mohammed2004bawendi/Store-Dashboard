<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Data\UpdateCustomerData;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;

class UpdateCustomerAction
{
    public function execute(Customer $customer, UpdateCustomerData $data): Customer
    {
        $customer->update($data->toArray());

        Cache::add('customers_cache_version', 1);
        Cache::increment('customers_cache_version');

        return $customer;
    }
}
