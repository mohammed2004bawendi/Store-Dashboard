<?php

namespace App\Domain\Customers\Data;

class UpdateCustomerData
{
    private const FIELDS = [
        'name',
        'phone',
        'address',
    ];

    private function __construct(
        private readonly array $attributes,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            attributes: array_intersect_key($data, array_flip(self::FIELDS)),
        );
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
