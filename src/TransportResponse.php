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

    public function isAccepted(): bool
    {
        return $this->status === 202;
    }
}
