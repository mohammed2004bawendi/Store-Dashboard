<?php

namespace App\Domain\Orders\Data;

class OrderItemData
{
    public function __construct(
        public readonly int $id,
        public readonly int $quantity,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            quantity: $data['quantity'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
        ];
    }
}
