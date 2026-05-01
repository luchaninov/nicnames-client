<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class DomainCheckResult
{
    /** @param PriceModel[] $price */
    public function __construct(
        public string $domainName,
        public string $availableFor,
        public string $tier,
        public array $price = [],
    ) {
    }

    /** @param array<string, mixed> $a */
    public static function createFromArray(array $a): self
    {
        return new self(
            domainName: (string) ($a['domainName'] ?? ''),
            availableFor: (string) ($a['availableFor'] ?? OperationModel::NONE),
            tier: (string) ($a['tier'] ?? 'UNKNOWN'),
            price: array_map(
                static fn(array $p) => PriceModel::createFromArray($p),
                (array) ($a['price'] ?? []),
            ),
        );
    }
}
