<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests\Webhook;

use Luchaninov\NicnamesClient\Webhook\WebhookVerifier;
use PHPUnit\Framework\TestCase;

class WebhookVerifierTest extends TestCase
{
    private const string SECRET = 'top-secret';
    private const string PAYLOAD = '{"eventId":"abc","eventType":"job_result"}';
    /** Unix ms — corresponds to seconds 1700000000 */
    private const string TIMESTAMP_MS = '1700000000000';
    private const int TIMESTAMP_SECONDS = 1700000000;

    public function testValidSignaturePasses(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        $signature = $verifier->sign(self::PAYLOAD, self::TIMESTAMP_MS);
        self::assertTrue($verifier->isValidSignature(self::PAYLOAD, self::TIMESTAMP_MS, $signature));
    }

    public function testTamperedPayloadFailsSignature(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        $signature = $verifier->sign(self::PAYLOAD, self::TIMESTAMP_MS);
        self::assertFalse($verifier->isValidSignature(self::PAYLOAD . 'X', self::TIMESTAMP_MS, $signature));
    }

    public function testWrongSecretFailsSignature(): void
    {
        $real = new WebhookVerifier('real');
        $signature = $real->sign(self::PAYLOAD, self::TIMESTAMP_MS);

        $impostor = new WebhookVerifier('wrong');
        self::assertFalse($impostor->isValidSignature(self::PAYLOAD, self::TIMESTAMP_MS, $signature));
    }

    public function testSignReturnsLowercaseHex(): void
    {
        $verifier = new WebhookVerifier('s');
        $signature = $verifier->sign('payload', '12345');
        self::assertSame(strtolower($signature), $signature);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $signature);
    }

    public function testIsValidPassesWithFreshTimestampInMilliseconds(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        $signature = $verifier->sign(self::PAYLOAD, self::TIMESTAMP_MS);
        $now = self::TIMESTAMP_SECONDS + 60;
        self::assertTrue($verifier->isValid(self::PAYLOAD, self::TIMESTAMP_MS, $signature, 300, $now));
    }

    public function testIsValidPassesWithSecondPrecisionTimestamp(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        $tsSeconds = (string) self::TIMESTAMP_SECONDS;
        $signature = $verifier->sign(self::PAYLOAD, $tsSeconds);
        $now = self::TIMESTAMP_SECONDS + 60;
        self::assertTrue($verifier->isValid(self::PAYLOAD, $tsSeconds, $signature, 300, $now));
    }

    public function testIsValidRejectsStaleTimestamp(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        $signature = $verifier->sign(self::PAYLOAD, self::TIMESTAMP_MS);
        $now = self::TIMESTAMP_SECONDS + 999;
        self::assertFalse($verifier->isValid(self::PAYLOAD, self::TIMESTAMP_MS, $signature, 300, $now));
    }

    public function testIsValidRejectsFutureTimestampBeyondTolerance(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        $signature = $verifier->sign(self::PAYLOAD, self::TIMESTAMP_MS);
        $now = self::TIMESTAMP_SECONDS - 999;
        self::assertFalse($verifier->isValid(self::PAYLOAD, self::TIMESTAMP_MS, $signature, 300, $now));
    }

    public function testIsValidRejectsNonNumericTimestamp(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        $signature = $verifier->sign(self::PAYLOAD, 'not-a-timestamp');
        self::assertFalse($verifier->isValid(self::PAYLOAD, 'not-a-timestamp', $signature, 300, self::TIMESTAMP_SECONDS));
    }

    public function testIsValidWithMaxAgeZeroSkipsFreshnessCheck(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        $signature = $verifier->sign(self::PAYLOAD, self::TIMESTAMP_MS);
        $now = self::TIMESTAMP_SECONDS + 99999;
        self::assertTrue($verifier->isValid(self::PAYLOAD, self::TIMESTAMP_MS, $signature, 0, $now));
    }

    public function testIsValidUsesCurrentTimeWhenNowOmitted(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        $tsMs = (string) (time() * 1000);
        $signature = $verifier->sign(self::PAYLOAD, $tsMs);
        self::assertTrue($verifier->isValid(self::PAYLOAD, $tsMs, $signature));
    }

    public function testIsValidShortCircuitsOnBadSignature(): void
    {
        $verifier = new WebhookVerifier(self::SECRET);
        self::assertFalse(
            $verifier->isValid(self::PAYLOAD, self::TIMESTAMP_MS, 'deadbeef', 300, self::TIMESTAMP_SECONDS),
        );
    }
}
