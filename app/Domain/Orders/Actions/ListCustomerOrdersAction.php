<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Data\OrderFiltersData;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListCustomerOrdersAction
{
    public function execute(Customer $customer, OrderFiltersData $filters): LengthAwarePaginator
    {
        return $customer->orders()
            ->when($filters->status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters->minTotalPrice, fn (Builder $query, int|string $min) => $query->where('total_price', '>=', $min))
            ->when($filters->maxTotalPrice, fn (Builder $query, int|string $max) => $query->where('total_price', '<=', $max))
            ->paginate();
    }
}
