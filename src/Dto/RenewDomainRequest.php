<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class RenewDomainRequest
{
    public function __construct(
        public PriceModel $price,
        public int $currentETS,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'price' => $this->price->toArray(),
            'currentETS' => $this->currentETS,
        ];
    }
}
