<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Data\CustomerFiltersData;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListCustomersAction
{
    public function execute(CustomerFiltersData $filters): LengthAwarePaginator
    {
        return Cache::remember($filters->cacheKey(), 60, function () use ($filters) {
            return $this->applyFilters(Customer::query(), $filters)->paginate();
        });
    }

    private function applyFilters(Builder $query, CustomerFiltersData $filters): Builder
    {
        return $query
            ->when($filters->search, fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters->phone, fn (Builder $query, string $phone) => $query->where('phone', 'like', "%{$phone}%"));
    }
}
