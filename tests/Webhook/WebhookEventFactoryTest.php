<?php /** @noinspection JsonEncodingApiUsageInspection */

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests\Webhook;

use Luchaninov\NicnamesClient\Dto\WebhookDomainStatusEvent;
use Luchaninov\NicnamesClient\Dto\WebhookEvent;
use Luchaninov\NicnamesClient\Dto\WebhookJobResultEvent;
use Luchaninov\NicnamesClient\Webhook\WebhookEventFactory;
use PHPUnit\Framework\TestCase;

class WebhookEventFactoryTest extends TestCase
{
    public function testJobResultEventDispatch(): void
    {
        $event = WebhookEventFactory::fromArray([
            'eventId' => '1f52cf35-b467-4653-ae86-ce1063a7eef6',
            'eventType' => 'job_result',
            'message' => 'Domain created successfully',
            'eventData' => [
                'jobId' => 'f8015046a6865383dac6',
                'code' => 440000,
            ],
        ]);

        self::assertInstanceOf(WebhookJobResultEvent::class, $event);
        self::assertSame('f8015046a6865383dac6', $event->jobId);
        self::assertSame(440000, $event->code);
    }

    public function testDomainStatusEventDispatch(): void
    {
        $event = WebhookEventFactory::fromArray([
            'eventId' => 'evt-1',
            'eventType' => 'domain_status',
            'message' => 'Domain expired',
            'eventData' => [
                'oid' => 'o54321',
                'type' => 'domain',
                'status' => ['expired'],
                'cts' => 1577836800,
                'uts' => 1577836800,
                'ets' => 1577836800,
                'domain' => ['name' => 'example.com', 'registrant' => 'c1'],
            ],
        ]);

        self::assertInstanceOf(WebhookDomainStatusEvent::class, $event);
        self::assertSame(['expired'], $event->eventData->status);
        self::assertNotNull($event->eventData->domain);
        self::assertSame('example.com', $event->eventData->domain->name);
    }

    public function testUnknownEventTypeReturnsBaseEvent(): void
    {
        $event = WebhookEventFactory::fromArray([
            'eventId' => 'evt-x',
            'eventType' => 'unknown_thing',
            'message' => 'Some message',
        ]);

        self::assertInstanceOf(WebhookEvent::class, $event);
        self::assertNotInstanceOf(WebhookJobResultEvent::class, $event);
        self::assertSame('unknown_thing', $event->eventType);
    }

    public function testFromJsonParsesString(): void
    {
        $json = json_encode([
            'eventId' => 'evt-json',
            'eventType' => 'job_result',
            'eventData' => ['jobId' => 'J', 'code' => 441000],
        ]);

        $event = WebhookEventFactory::fromJson($json);
        self::assertInstanceOf(WebhookJobResultEvent::class, $event);
        self::assertSame('J', $event->jobId);
    }
}
