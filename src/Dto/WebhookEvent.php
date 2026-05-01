<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

readonly class WebhookEvent
{
    public function __construct(
        public string $eventId,
        public string $eventType,
        public ?string $message = null,
    ) {
    }
}
