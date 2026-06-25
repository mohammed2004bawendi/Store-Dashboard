<?php

namespace App\Domain\Customers\Data;

class CreateCustomerData
{
    public function __construct(
        public readonly string $name,
        public readonly string $phone,
        public readonly string $address,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            phone: $data['phone'],
            address: $data['address'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
        ];
    }
}
