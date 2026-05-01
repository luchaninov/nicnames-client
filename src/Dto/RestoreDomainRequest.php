<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class RestoreDomainRequest
{
    public function __construct(
        public PriceModel $price,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'price' => $this->price->toArray(),
        ];
    }
}
