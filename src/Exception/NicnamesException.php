<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Exception;

class NicnamesException extends \RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        public readonly ?string $traceId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
