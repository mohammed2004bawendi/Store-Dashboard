<?php

namespace App\Domain\Orders\Data;

class UpdateOrderData
{
    /**
     * @param array<int, OrderItemData> $products
     */
    private function __construct(
        public readonly ?string $status,
        public readonly mixed $totalPrice,
        public readonly array $customer,
        public readonly array $products,
        private readonly array $attributes,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? null,
            totalPrice: $data['total_price'] ?? null,
            customer: $data['customer'] ?? [],
            products: array_map(
                fn (array $product) => OrderItemData::fromArray($product),
                $data['products'] ?? [],
            ),
            attributes: $data,
        );
    }

    public function hasProducts(): bool
    {
        return !empty($this->products);
    }

    public function hasCustomer(): bool
    {
        return !empty($this->customer);
    }

    public function hasTotalPrice(): bool
    {
        return array_key_exists('total_price', $this->attributes);
    }
}
