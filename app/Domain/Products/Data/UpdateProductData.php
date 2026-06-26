<?php

namespace App\Domain\Products\Data;

class UpdateProductData
{
    private const FIELDS = [
        'name',
        'description',
        'price',
        'quantity',
        'status',
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
