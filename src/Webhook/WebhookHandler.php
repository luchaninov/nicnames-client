<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Webhook;

use JsonException;
use Luchaninov\NicnamesClient\Dto\WebhookEvent;

/**
 * One-shot helper that turns an incoming Nicnames webhook into a typed event.
 *
 * Combines signature verification, timestamp freshness, and JSON decoding into a single call so the
 * application layer just does:
 *
 *     $event = $handler->handle($_POST);
 *
 * Any validation failure raises {@see WebhookException}; map it to HTTP 401 in your controller.
 */
final readonly class WebhookHandler
{
    public function __construct(
        private WebhookVerifier $verifier,
        private int $maxAgeSeconds = WebhookVerifier::DEFAULT_MAX_AGE_SECONDS,
    ) {
    }

    /**
     * Validate and decode an incoming webhook posted as `application/x-www-form-urlencoded` with
     * `object`, `timestamp`, and `signature` fields.
     *
     * @param array<string, mixed> $form
     * @param int|null $now Override "now" (Unix seconds) for deterministic tests.
     *
     * @throws WebhookException on missing fields, bad signature, stale timestamp, or invalid JSON.
     */
    public function handle(array $form, ?int $now = null): WebhookEvent
    {
        $payload = isset($form['object']) ? (string) $form['object'] : '';
        $timestamp = isset($form['timestamp']) ? (string) $form['timestamp'] : '';
        $signature = isset($form['signature']) ? (string) $form['signature'] : '';

        if ($payload === '' || $timestamp === '' || $signature === '') {
            throw new WebhookException('Webhook is missing required fields (object, timestamp, signature).');
        }
        if (!$this->verifier->isValid($payload, $timestamp, $signature, $this->maxAgeSeconds, $now)) {
            throw new WebhookException('Webhook signature or timestamp validation failed.');
        }

        try {
            return WebhookEventFactory::fromJson($payload);
        } catch (JsonException $e) {
            throw new WebhookException('Webhook payload is not valid JSON: ' . $e->getMessage(), 0, $e);
        }
    }
}
