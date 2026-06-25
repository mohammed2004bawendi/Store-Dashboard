<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Data\CreateCustomerData;
use App\Models\Customer;

class CreateCustomerAction
{
    public function execute(CreateCustomerData $data): Customer
    {
        return Customer::create($data->toArray());
    }
}
