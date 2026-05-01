<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class WebhookDomainStatusEvent extends WebhookEvent
{
    public function __construct(
        string $eventId,
        string $eventType,
        ?string $message,
        public OrderDomainModel $eventData,
    ) {
        parent::__construct($eventId, $eventType, $message);
    }

    /** @param array<string, mixed> $a */
    public static function createFromArray(array $a): self
    {
        return new self(
            eventId: (string) ($a['eventId'] ?? ''),
            eventType: (string) ($a['eventType'] ?? 'domain_status'),
            message: isset($a['message']) ? (string) $a['message'] : null,
            eventData: OrderDomainModel::createFromArray((array) ($a['eventData'] ?? [])),
        );
    }
}
