<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Data\OrderFiltersData;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListOrdersAction
{
    public function execute(OrderFiltersData $filters): LengthAwarePaginator
    {
        return Cache::remember($filters->cacheKey(), 60, function () use ($filters) {
            return $this->applyFilters(
                Order::query()->with(['customer', 'products'])->whereHas('customer'),
                $filters,
            )->paginate();
        });
    }

    public function meta(LengthAwarePaginator $orders): array
    {
        return [
            'total_orders' => $orders->count(),
            'total_amount' => $orders->sum('total_price'),
        ];
    }

    private function applyFilters(Builder $query, OrderFiltersData $filters): Builder
    {
        return $query
            ->when($filters->status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters->minTotalPrice, fn (Builder $query, int|string $min) => $query->where('total_price', '>=', $min))
            ->when($filters->maxTotalPrice, fn (Builder $query, int|string $max) => $query->where('total_price', '<=', $max))
            ->when($filters->search, function (Builder $query, int|string $search) {
                $query->where('id', $search)
                    ->orWhereHas('customer', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
            });
    }
}
