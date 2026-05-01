<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class PriceModel
{
    public function __construct(
        public float $amt,
        public int $ccy,
        public string $op,
        public PeriodModel $period,
    ) {
    }

    /** @param array<string, mixed> $a */
    public static function createFromArray(array $a): self
    {
        return new self(
            amt: (float) ($a['amt'] ?? 0),
            ccy: (int) ($a['ccy'] ?? 0),
            op: (string) ($a['op'] ?? OperationModel::NONE),
            period: PeriodModel::createFromArray((array) ($a['period'] ?? [])),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'amt' => $this->amt,
            'ccy' => $this->ccy,
            'op' => $this->op,
            'period' => $this->period->toArray(),
        ];
    }
}
