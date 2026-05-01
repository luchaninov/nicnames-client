<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests\Dto;

use Luchaninov\NicnamesClient\Dto\WebhookDomainStatusEvent;
use Luchaninov\NicnamesClient\Dto\WebhookJobResultEvent;
use PHPUnit\Framework\TestCase;

class WebhookEventDtoTest extends TestCase
{
    public function testWebhookJobResultEventDirectDecode(): void
    {
        $event = WebhookJobResultEvent::createFromArray([
            'eventId' => 'evt-1',
            'eventType' => 'job_result',
            'message' => 'Job completed',
            'eventData' => [
                'jobId' => 'JOB-1',
                'code' => 441000,
            ],
        ]);
        self::assertSame('evt-1', $event->eventId);
        self::assertSame('job_result', $event->eventType);
        self::assertSame('Job completed', $event->message);
        self::assertSame('JOB-1', $event->jobId);
        self::assertSame(441000, $event->code);
    }

    public function testWebhookJobResultEventWithDefaults(): void
    {
        $event = WebhookJobResultEvent::createFromArray([]);
        self::assertSame('', $event->eventId);
        self::assertSame('job_result', $event->eventType);
        self::assertNull($event->message);
        self::assertSame('', $event->jobId);
        self::assertSame(0, $event->code);
    }

    public function testWebhookDomainStatusEventDirectDecode(): void
    {
        $event = WebhookDomainStatusEvent::createFromArray([
            'eventId' => 'evt-2',
            'eventType' => 'domain_status',
            'eventData' => [
                'oid' => 'o1',
                'type' => 'domain',
                'status' => ['expired'],
                'cts' => 1,
                'uts' => 1,
                'ets' => 1,
                'domain' => ['name' => 'example.com', 'registrant' => 'c1'],
            ],
        ]);
        self::assertSame('evt-2', $event->eventId);
        self::assertSame('domain_status', $event->eventType);
        self::assertNull($event->message);
        self::assertSame(['expired'], $event->eventData->status);
        self::assertSame('example.com', $event->eventData->domain?->name);
    }
}
