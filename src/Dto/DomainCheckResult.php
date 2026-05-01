<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class DomainCheckResult
{
    /** @param PriceModel[] $price */
    public function __construct(
        public string $domainName,
        public OperationModel $availableFor,
        public TierModel $tier,
        public array $price = [],
    ) {
    }

    /** @param array<string, mixed> $a */
    public static function createFromArray(array $a): self
    {
        $availableFor = isset($a['availableFor']) ? OperationModel::tryFrom((string) $a['availableFor']) : null;
        $tier = isset($a['tier']) ? TierModel::tryFrom((string) $a['tier']) : null;

        return new self(
            domainName: (string) ($a['domainName'] ?? ''),
            availableFor: $availableFor ?? OperationModel::NONE,
            tier: $tier ?? TierModel::UNKNOWN,
            price: array_map(
                static fn(array $p) => PriceModel::createFromArray($p),
                (array) ($a['price'] ?? []),
            ),
        );
    }
}
