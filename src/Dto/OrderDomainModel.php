<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class OrderDomainModel extends OrderModel
{
    /** @param string[] $status */
    public function __construct(
        string $oid,
        string $type,
        array $status,
        int $cts,
        int $uts,
        ?int $ets,
        public ?DomainModel $domain = null,
    ) {
        parent::__construct($oid, $type, $status, $cts, $uts, $ets);
    }

    /** @param array<string, mixed> $a */
    public static function createFromArray(array $a): self
    {
        return new self(
            oid: (string) ($a['oid'] ?? ''),
            type: (string) ($a['type'] ?? 'domain'),
            status: array_values(array_map(static fn($v) => (string) $v, (array) ($a['status'] ?? []))),
            cts: (int) ($a['cts'] ?? 0),
            uts: (int) ($a['uts'] ?? 0),
            ets: isset($a['ets']) ? (int) $a['ets'] : null,
            domain: isset($a['domain']) && is_array($a['domain'])
                ? DomainModel::createFromArray($a['domain'])
                : null,
        );
    }
}
