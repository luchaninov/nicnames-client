<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class EmailVerificationStatus
{
    public function __construct(
        public int $code,
        public ?string $email = null,
    ) {
    }

    /** @param array<string, mixed> $a */
    public static function createFromArray(array $a): self
    {
        return new self(
            code: (int) ($a['code'] ?? 0),
            email: isset($a['email']) ? (string) $a['email'] : null,
        );
    }
}
