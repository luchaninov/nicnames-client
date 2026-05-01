<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient;

final readonly class TransportResponse
{
    /** @param array<string, mixed> $body */
    public function __construct(
        public int $status,
        public array $body,
    ) {
    }

    /** Whether the API parked the work for asynchronous completion (HTTP 202 Accepted). */
    public function isAsync(): bool
    {
        return $this->status === 202;
    }
}
