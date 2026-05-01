<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Webhook;

final readonly class WebhookVerifier
{
    public const int DEFAULT_MAX_AGE_SECONDS = 300;

    public function __construct(
        private string $secret,
    ) {
    }

    /**
     * Verify the HMAC-SHA256 signature carried by an incoming webhook request.
     *
     * Per the Nicnames spec, the signature is `hash_hmac('sha256', $payload . $timestamp, $secret)`
     * (lowercase hex), where `$payload` is the JSON `object` field and `$timestamp` is the
     * timestamp string sent alongside it (Unix timestamp in milliseconds).
     */
    public function isValidSignature(string $payload, string $timestamp, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload . $timestamp, $this->secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Combined check: signature is valid AND the timestamp is within `$maxAgeSeconds` of now (anti-replay).
     *
     * Pass `0` or a negative value for `$maxAgeSeconds` to skip the freshness check (not recommended).
     */
    public function isValid(
        string $payload,
        string $timestamp,
        string $signature,
        int $maxAgeSeconds = self::DEFAULT_MAX_AGE_SECONDS,
        ?int $now = null,
    ): bool {
        if (!$this->isValidSignature($payload, $timestamp, $signature)) {
            return false;
        }
        if ($maxAgeSeconds <= 0) {
            return true;
        }

        $sentAt = $this->timestampToSeconds($timestamp);
        if ($sentAt === null) {
            return false;
        }

        $current = $now ?? time();

        return abs($current - $sentAt) <= $maxAgeSeconds;
    }

    public function sign(string $payload, string $timestamp): string
    {
        return hash_hmac('sha256', $payload . $timestamp, $this->secret);
    }

    /**
     * The Nicnames spec defines `timestamp` as Unix time in milliseconds. Accept that, but tolerate
     * second-precision values too (some senders may not use ms).
     */
    private function timestampToSeconds(string $timestamp): ?int
    {
        if ($timestamp === '' || !ctype_digit($timestamp)) {
            return null;
        }
        $value = (int) $timestamp;
        // Treat 13-digit values as milliseconds, 10-digit (or shorter) values as seconds.
        if (strlen($timestamp) >= 13) {
            return intdiv($value, 1000);
        }

        return $value;
    }
}
