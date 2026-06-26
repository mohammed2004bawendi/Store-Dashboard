<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Data\CreateCustomerData;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;

class CreateCustomerAction
{
    public function execute(CreateCustomerData $data): Customer
    {
        $customer = Customer::create($data->toArray());

        Cache::add('customers_cache_version', 1);
        Cache::increment('customers_cache_version');

        return $customer;
    }
}
