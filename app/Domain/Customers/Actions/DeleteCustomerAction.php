<?php

namespace App\Domain\Customers\Actions;

use App\Models\Customer;

class DeleteCustomerAction
{
    public function execute(Customer $customer): void
    {
        $customer->delete();
    }
}
