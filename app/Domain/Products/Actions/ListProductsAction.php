<?php

namespace App\Domain\Products\Actions;

use App\Domain\Products\Data\ProductFiltersData;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListProductsAction
{
    public function execute(ProductFiltersData $filters): LengthAwarePaginator
    {
        return Cache::remember($filters->cacheKey(), 60, function () use ($filters) {
            return $this->applyFilters(Product::query(), $filters)->paginate();
        });
    }

    private function applyFilters(Builder $query, ProductFiltersData $filters): Builder
    {
        return $query
            ->when($filters->status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters->minQuantity, fn (Builder $query, int|string $quantity) => $query->where('quantity', '>=', $quantity))
            ->when($filters->maxQuantity, fn (Builder $query, int|string $quantity) => $query->where('quantity', '<=', $quantity))
            ->when($filters->minPrice, fn (Builder $query, int|string $price) => $query->where('price', '>=', $price))
            ->when($filters->maxPrice, fn (Builder $query, int|string $price) => $query->where('price', '<=', $price))
            ->when($filters->search, fn (Builder $query, string $search) => $query->where('name', 'like', "%{$search}%"));
    }
}
