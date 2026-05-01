<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Webhook;

final readonly class WebhookVerifier
{
    public function __construct(
        private string $secret,
    ) {
    }

    /**
     * Verify the HMAC-SHA256 signature carried by an incoming webhook request.
     *
     * Per the Nicnames spec, the signature is `hash_hmac('sha256', $payload . $timestamp, $secret)`
     * (lowercase hex), where `$payload` is the JSON `object` field and `$timestamp` is the
     * timestamp string sent alongside it.
     */
    public function isValid(string $payload, string $timestamp, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload . $timestamp, $this->secret);

        return hash_equals($expected, $signature);
    }

    public function sign(string $payload, string $timestamp): string
    {
        return hash_hmac('sha256', $payload . $timestamp, $this->secret);
    }
}
