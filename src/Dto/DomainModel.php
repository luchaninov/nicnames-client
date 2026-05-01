<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class DomainModel
{
    /** @param string[] $ns */
    public function __construct(
        public string $name,
        public string $registrant,
        public ?string $admin = null,
        public ?string $tech = null,
        public ?string $billing = null,
        public array $ns = [],
        public ?string $w3a = null,
    ) {
    }

    /** @param array<string, mixed> $a */
    public static function createFromArray(array $a): self
    {
        return new self(
            name: (string) ($a['name'] ?? ''),
            registrant: (string) ($a['registrant'] ?? ''),
            admin: isset($a['admin']) ? (string) $a['admin'] : null,
            tech: isset($a['tech']) ? (string) $a['tech'] : null,
            billing: isset($a['billing']) ? (string) $a['billing'] : null,
            ns: array_values(array_map(static fn($v) => (string) $v, (array) ($a['ns'] ?? []))),
            w3a: isset($a['w3a']) ? (string) $a['w3a'] : null,
        );
    }
}
