<?php

namespace App\Domain\Products\Data;

class CreateProductData
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly int $price,
        public readonly int $quantity,
        public readonly string $status,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'],
            price: $data['price'],
            quantity: $data['quantity'],
            status: $data['status'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'status' => $this->status,
        ];
    }
}
