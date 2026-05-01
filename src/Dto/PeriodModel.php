<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class PeriodModel
{
    public function __construct(
        public string $unit,
        public int $value,
    ) {
    }

    /** @param array<string, mixed> $a */
    public static function createFromArray(array $a): self
    {
        return new self(
            unit: (string) ($a['unit'] ?? PeriodUnitModel::YEARS),
            value: (int) ($a['value'] ?? 1),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'unit' => $this->unit,
            'value' => $this->value,
        ];
    }
}
