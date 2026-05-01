<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests\Webhook;

use Luchaninov\NicnamesClient\Webhook\WebhookVerifier;
use PHPUnit\Framework\TestCase;

class WebhookVerifierTest extends TestCase
{
    public function testValidSignaturePasses(): void
    {
        $secret = 'top-secret';
        $payload = '{"eventId":"abc","eventType":"job_result"}';
        $timestamp = '1700000000000';
        $expected = hash_hmac('sha256', $payload . $timestamp, $secret);

        $verifier = new WebhookVerifier($secret);
        self::assertTrue($verifier->isValid($payload, $timestamp, $expected));
    }

    public function testTamperedPayloadFails(): void
    {
        $secret = 'top-secret';
        $payload = '{"eventId":"abc","eventType":"job_result"}';
        $timestamp = '1700000000000';
        $signature = hash_hmac('sha256', $payload . $timestamp, $secret);

        $verifier = new WebhookVerifier($secret);
        self::assertFalse($verifier->isValid($payload . 'X', $timestamp, $signature));
    }

    public function testWrongSecretFails(): void
    {
        $payload = '{"eventId":"abc","eventType":"job_result"}';
        $timestamp = '1700000000000';
        $signature = hash_hmac('sha256', $payload . $timestamp, 'real-secret');

        $verifier = new WebhookVerifier('wrong-secret');
        self::assertFalse($verifier->isValid($payload, $timestamp, $signature));
    }

    public function testSignReturnsLowercaseHex(): void
    {
        $verifier = new WebhookVerifier('s');
        $signature = $verifier->sign('payload', '12345');
        self::assertSame(strtolower($signature), $signature);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $signature);
    }
}
