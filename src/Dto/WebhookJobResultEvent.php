<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class WebhookJobResultEvent extends WebhookEvent
{
    public function __construct(
        string $eventId,
        string $eventType,
        ?string $message,
        public string $jobId,
        public int $code,
    ) {
        parent::__construct($eventId, $eventType, $message);
    }

    /** @param array<string, mixed> $a */
    public static function createFromArray(array $a): self
    {
        $eventData = (array) ($a['eventData'] ?? []);

        return new self(
            eventId: (string) ($a['eventId'] ?? ''),
            eventType: (string) ($a['eventType'] ?? 'job_result'),
            message: isset($a['message']) ? (string) $a['message'] : null,
            jobId: (string) ($eventData['jobId'] ?? ''),
            code: (int) ($eventData['code'] ?? 0),
        );
    }
}
