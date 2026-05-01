<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Webhook;

use Luchaninov\NicnamesClient\Dto\WebhookDomainStatusEvent;
use Luchaninov\NicnamesClient\Dto\WebhookEvent;
use Luchaninov\NicnamesClient\Dto\WebhookJobResultEvent;

final class WebhookEventFactory
{
    /**
     * Build a typed webhook event DTO from the decoded JSON `object` payload.
     *
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): WebhookEvent
    {
        $eventType = (string) ($payload['eventType'] ?? '');

        return match ($eventType) {
            'job_result' => WebhookJobResultEvent::createFromArray($payload),
            'domain_status' => WebhookDomainStatusEvent::createFromArray($payload),
            default => new WebhookEvent(
                eventId: (string) ($payload['eventId'] ?? ''),
                eventType: $eventType,
                message: isset($payload['message']) ? (string) $payload['message'] : null,
            ),
        };
    }

    public static function fromJson(string $json): WebhookEvent
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return self::fromArray($decoded);
    }
}
