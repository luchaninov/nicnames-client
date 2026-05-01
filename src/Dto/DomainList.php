<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class DomainList
{
    /** @param OrderDomainModel[] $list */
    public function __construct(
        public int $total,
        public array $list,
    ) {
    }

    /** @param array<string, mixed> $a */
    public static function createFromArray(array $a): self
    {
        return new self(
            total: (int) ($a['total'] ?? 0),
            list: array_map(
                static fn(array $d) => OrderDomainModel::createFromArray($d),
                (array) ($a['list'] ?? []),
            ),
        );
    }
}
