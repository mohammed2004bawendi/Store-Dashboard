<?php

namespace App\Domain\Orders\Data;

class OrderFiltersData
{
    private function __construct(
        public readonly ?string $status,
        public readonly int|string|null $minTotalPrice,
        public readonly int|string|null $maxTotalPrice,
        public readonly int|string|null $search,
        public readonly int|string $page,
        private readonly array $attributes,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? null,
            minTotalPrice: $data['min_total_price'] ?? null,
            maxTotalPrice: $data['max_total_price'] ?? null,
            search: $data['search'] ?? null,
            page: $data['page'] ?? 1,
            attributes: $data,
        );
    }

    public function cacheKey(): string
    {
        return 'orders.page.' . $this->page . '.' . md5(json_encode($this->attributes));
    }
}
