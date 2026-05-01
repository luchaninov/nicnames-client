<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class DomainOperationResult
{
    private function __construct(
        public ?OrderDomainModel $order,
        public ?string $jobId,
    ) {
    }

    public static function fromOrder(OrderDomainModel $order): self
    {
        return new self($order, null);
    }

    public static function fromJob(string $jobId): self
    {
        return new self(null, $jobId);
    }

    public function isAsync(): bool
    {
        return $this->jobId !== null;
    }
}
