<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class DomainOperationResult
{
    public function __construct(
        public ?OrderDomainModel $order = null,
        public ?string $jobId = null,
    ) {
    }

    public static function fromOrder(OrderDomainModel $order): self
    {
        return new self(order: $order);
    }

    public static function fromJob(string $jobId): self
    {
        return new self(jobId: $jobId);
    }

    public function isAsync(): bool
    {
        return $this->jobId !== null;
    }
}
