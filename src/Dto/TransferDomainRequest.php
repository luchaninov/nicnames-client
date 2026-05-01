<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class TransferDomainRequest
{
    public function __construct(
        public PriceModel $price,
        public string $registrant,
        public string $authCode,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'price' => $this->price->toArray(),
            'registrant' => $this->registrant,
            'authCode' => $this->authCode,
        ];
    }
}
