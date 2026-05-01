# CLAUDE.md

## Project

PHP client library for the Nicnames domain registrar REST API v2 (`https://api.nicnames.com/2`).

OpenAPI spec lives in `doc/openapi.json`.

## Commands

- `composer install` — install dependencies
- `vendor/bin/phpunit` — run tests
- `vendor/bin/phpstan analyze` — static analysis (level 5)

## Architecture

- `NicnamesClient` — single class with all 14 API methods
- `HttpTransport` — REST transport over Symfony HttpClient; injects `x-api-key` header, decodes 4XX/5XX into typed exceptions
- `TransportResponse` — small `(status, body)` wrapper so the client can branch between 200/201 and 202 (`jobId`)
- `Dto/` — `final readonly` classes with `createFromArray()` factories on response models and `toArray()` on request models
- `Dto/DomainOperationResult` — wrapper returned by every operation that may go async; carries either `->order` (sync) or `->jobId` (async)
- `Webhook/WebhookVerifier` — HMAC-SHA256 (`hash_equals`) signature verifier for incoming webhooks
- `Webhook/WebhookEventFactory` — dispatches by `eventType` to `WebhookJobResultEvent` or `WebhookDomainStatusEvent`
- `Exception/` — typed hierarchy mapping API result codes (442001–442012) to specific exceptions; base `NicnamesException` carries `code`, `message`, `traceId`

## Manual testing

- `examples/test.php` — smoke-test script that exercises the real API (list domains, list contacts, check availability)
- Requires `.env.local` in project root (gitignored) in INI format:
  ```
  NICNAMES_API_KEY=your-api-key
  NICNAMES_BASE_URL=https://api.nicnames.com/2  ; optional
  NICNAMES_DEFAULT_REGISTRANT=c987654321        ; optional, used by register.php/transfer.php
  ```
- Run: `php examples/test.php`

## Code style

- PHP 8.4+, `declare(strict_types=1)` in every file
- Namespace: `Luchaninov\NicnamesClient`
- `final readonly` classes for DTOs (the inheritance pair `OrderModel` / `OrderDomainModel` keeps the parent non-final because the OpenAPI discriminator allows future order subtypes)
- Tests use `Symfony\Component\HttpClient\MockHttpClient` + `MockResponse`
- PHPStan level 5, PHPUnit 13
