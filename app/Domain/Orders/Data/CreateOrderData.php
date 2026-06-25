<?php

namespace App\Domain\Orders\Data;

class CreateOrderData
{
    /**
     * @param array<int, OrderItemData> $products
     */
    public function __construct(
        public readonly string $name,
        public readonly string $phone,
        public readonly string $address,
        public readonly ?string $status,
        public readonly array $products,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            phone: $data['phone'],
            address: $data['address'],
            status: $data['status'] ?? null,
            products: array_map(
                fn (array $product) => OrderItemData::fromArray($product),
                $data['products'],
            ),
        );
    }
}
