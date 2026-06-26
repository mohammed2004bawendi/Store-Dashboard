<?php

namespace App\Domain\Products\Data;

use Illuminate\Support\Facades\Cache;

class ProductFiltersData
{
    private function __construct(
        public readonly ?string $search,
        public readonly ?string $status,
        public readonly int|string|null $minQuantity,
        public readonly int|string|null $maxQuantity,
        public readonly int|string|null $minPrice,
        public readonly int|string|null $maxPrice,
        public readonly int|string $page,
        private readonly array $attributes,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            status: $data['status'] ?? null,
            minQuantity: $data['min_quantity'] ?? null,
            maxQuantity: $data['max_quantity'] ?? null,
            minPrice: $data['min_price'] ?? null,
            maxPrice: $data['max_price'] ?? null,
            page: $data['page'] ?? 1,
            attributes: $data,
        );
    }

    public function cacheKey(): string
    {
        $version = Cache::rememberForever('products_cache_version', fn () => 1);
        $params = $this->attributes;
        unset($params['page']);
        ksort($params);

        return "products.v{$version}.page.{$this->page}." . md5(json_encode($params));
    }
}
