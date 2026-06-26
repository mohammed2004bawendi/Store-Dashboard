<?php

namespace App\Domain\Customers\Data;

use Illuminate\Support\Facades\Cache;

class CustomerFiltersData
{
    private function __construct(
        public readonly ?string $search,
        public readonly ?string $phone,
        public readonly int|string $page,
        private readonly array $attributes,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            phone: $data['phone'] ?? null,
            page: $data['page'] ?? 1,
            attributes: $data,
        );
    }

    public function cacheKey(): string
    {
        $version = Cache::rememberForever('customers_cache_version', fn () => 1);
        $params = $this->attributes;
        unset($params['page']);
        ksort($params);

        return "customers.v{$version}.page.{$this->page}." . md5(json_encode($params));
    }
}
