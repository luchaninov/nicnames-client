<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

readonly class OrderModel
{
    /** @param string[] $status */
    public function __construct(
        public string $oid,
        public string $type,
        public array $status,
        public int $cts,
        public int $uts,
        public ?int $ets = null,
    ) {
    }

    /**
     * Decode an order, dispatching to the matching subclass based on the OpenAPI `type` discriminator.
     *
     * @param array<string, mixed> $a
     */
    public static function createFromArray(array $a): self
    {
        $type = (string) ($a['type'] ?? 'common');
        if ($type === 'domain') {
            return OrderDomainModel::createFromArray($a);
        }

        return new self(
            oid: (string) ($a['oid'] ?? ''),
            type: $type,
            status: array_values(array_map(static fn(mixed $v) => (string) $v, (array) ($a['status'] ?? []))),
            cts: (int) ($a['cts'] ?? 0),
            uts: (int) ($a['uts'] ?? 0),
            ets: isset($a['ets']) ? (int) $a['ets'] : null,
        );
    }
}
