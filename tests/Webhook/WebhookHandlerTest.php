<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests\Webhook;

use Luchaninov\NicnamesClient\Dto\WebhookJobResultEvent;
use Luchaninov\NicnamesClient\Webhook\WebhookException;
use Luchaninov\NicnamesClient\Webhook\WebhookHandler;
use Luchaninov\NicnamesClient\Webhook\WebhookVerifier;
use PHPUnit\Framework\TestCase;

class WebhookHandlerTest extends TestCase
{
    private const int FROZEN_NOW = 1700000000;

    /** @return array{form: array<string, string>, verifier: WebhookVerifier} */
    private function makePayload(string $secret = 's', ?int $atSecondsAgo = null): array
    {
        $verifier = new WebhookVerifier($secret);
        $payload = json_encode([
            'eventId' => 'evt-1',
            'eventType' => 'job_result',
            'eventData' => ['jobId' => 'JOB-1', 'code' => 441000],
        ], JSON_THROW_ON_ERROR);
        $timestamp = (string) ((self::FROZEN_NOW - ($atSecondsAgo ?? 0)) * 1000);
        $signature = $verifier->sign($payload, $timestamp);

        return [
            'form' => [
                'object' => $payload,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ],
            'verifier' => $verifier,
        ];
    }

    public function testHandleReturnsTypedEventOnHappyPath(): void
    {
        ['form' => $form, 'verifier' => $verifier] = $this->makePayload();
        $handler = new WebhookHandler($verifier);

        $event = $handler->handle($form, self::FROZEN_NOW);
        self::assertInstanceOf(WebhookJobResultEvent::class, $event);
        self::assertSame('JOB-1', $event->jobId);
    }

    public function testHandleRejectsMissingFields(): void
    {
        $handler = new WebhookHandler(new WebhookVerifier('s'));
        $this->expectException(WebhookException::class);
        $this->expectExceptionMessageMatches('/missing required fields/');
        $handler->handle(['object' => '{}', 'timestamp' => '123']);
    }

    public function testHandleRejectsBadSignature(): void
    {
        ['form' => $form, 'verifier' => $verifier] = $this->makePayload();
        $form['signature'] = 'deadbeef';
        $handler = new WebhookHandler($verifier);

        $this->expectException(WebhookException::class);
        $this->expectExceptionMessageMatches('/signature or timestamp/');
        $handler->handle($form, self::FROZEN_NOW);
    }

    public function testHandleRejectsStaleTimestamp(): void
    {
        ['form' => $form, 'verifier' => $verifier] = $this->makePayload(atSecondsAgo: 99999);
        $handler = new WebhookHandler($verifier, maxAgeSeconds: 60);

        $this->expectException(WebhookException::class);
        $handler->handle($form, self::FROZEN_NOW);
    }

    public function testHandleRejectsInvalidJson(): void
    {
        $verifier = new WebhookVerifier('s');
        $payload = 'not-json';
        $timestamp = (string) (self::FROZEN_NOW * 1000);
        $signature = $verifier->sign($payload, $timestamp);
        $handler = new WebhookHandler($verifier);

        $this->expectException(WebhookException::class);
        $this->expectExceptionMessageMatches('/not valid JSON/');
        $handler->handle(
            [
                'object' => $payload,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ],
            self::FROZEN_NOW,
        );
    }
}
