<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Data\UpdateCustomerData;
use App\Models\Customer;

class UpdateCustomerAction
{
    public function execute(Customer $customer, UpdateCustomerData $data): Customer
    {
        $customer->update($data->toArray());

        return $customer;
    }
}
