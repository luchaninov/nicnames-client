# Nicnames API v2 PHP Client — Scaffold

**Status:** implemented (2026-05-01) — `composer install`, `vendor/bin/phpunit` (80 tests / 262 assertions / 100% line coverage),
`vendor/bin/phpstan analyze` (level 5) all green.

## Context

A PHP client library for the Nicnames domain registrar (REST API v2, `https://api.nicnames.com/2`).
The Nicnames repo at `/Users/vl/www/luchaninov/nicnames-client` started empty except for `doc/openapi.json`.
The library mirrors the reference's minimal architecture (single-class client, readonly DTOs, typed exception hierarchy,
Symfony HttpClient transport, PHPUnit + PHPStan-level-5) while adapting to Nicnames' differences:

- REST (path-based) not JSON-RPC
- API-key auth via `x-api-key` HTTP header (no login flow)
- Async `202 Accepted` responses with `jobId` (handled by a result wrapper DTO)
- Documented webhooks with HMAC-SHA256 signatures (helper included)

## Decisions (from clarifying questions)

- Package: `luchaninov/nicnames-client`, namespace `Luchaninov\NicnamesClient\`
- Webhooks, examples/, CLAUDE.md, README.md all included
- 202 responses return a `DomainOperationResult` wrapper with nullable `->order` and `->jobId`

## Final layout

```
nicnames-client/
├── composer.json
├── phpstan.neon
├── phpunit.xml.dist
├── .editorconfig
├── .gitignore
├── README.md
├── CLAUDE.md
├── doc/openapi.json
├── src/
│   ├── NicnamesClient.php           # single entry-point client, all 14 endpoints
│   ├── HttpTransport.php            # REST transport over Symfony HttpClient
│   ├── TransportResponse.php        # (status, body) wrapper, isAccepted() === 202
│   ├── Webhook/
│   │   ├── WebhookVerifier.php      # HMAC-SHA256 (constant-time compare)
│   │   └── WebhookEventFactory.php  # eventType-discriminated → typed DTO
│   ├── Dto/
│   │   ├── ListParams.php
│   │   ├── OperationModel.php       # const-only enum-style
│   │   ├── PeriodUnitModel.php      # const-only enum-style
│   │   ├── PeriodModel.php
│   │   ├── PriceModel.php
│   │   ├── ContactModel.php
│   │   ├── ContactList.php
│   │   ├── CreateContactRequest.php
│   │   ├── DomainModel.php
│   │   ├── OrderModel.php
│   │   ├── OrderDomainModel.php
│   │   ├── DomainList.php
│   │   ├── DomainCheckResult.php
│   │   ├── DomainOperationResult.php
│   │   ├── CreateDomainRequest.php
│   │   ├── RenewDomainRequest.php
│   │   ├── RestoreDomainRequest.php
│   │   ├── TransferDomainRequest.php
│   │   ├── UpdateWhoisPrivacyRequest.php
│   │   ├── EmailVerificationStatus.php
│   │   ├── WebhookEvent.php
│   │   ├── WebhookJobResultEvent.php
│   │   └── WebhookDomainStatusEvent.php
│   └── Exception/
│       ├── NicnamesException.php        # base — code, message, traceId
│       ├── TransportException.php       # HTTP/network failures
│       ├── RemoteException.php          # 442001
│       ├── ApiException.php             # 442002
│       ├── ForbiddenException.php       # 442003
│       ├── UnknownStatusException.php   # 442006
│       ├── NotFoundException.php        # 442007
│       ├── StatusProhibitedException.php# 442008
│       ├── InvalidParamPolicyException.php # 442009
│       ├── InvalidParamValueException.php  # 442010
│       ├── ParamRequiredException.php   # 442011
│       └── UnauthorizedException.php    # 442012
├── tests/
│   ├── NicnamesClientTest.php
│   ├── HttpTransportTest.php
│   ├── Webhook/
│   │   ├── WebhookVerifierTest.php
│   │   └── WebhookEventFactoryTest.php
│   └── Dto/
│       ├── OrderDomainModelTest.php
│       ├── ContactModelTest.php
│       └── DomainOperationResultTest.php
└── examples/
    ├── _lib.php
    ├── test.php          # smoke: list domains, list contacts, check availability
    ├── check.php         # bulk /domain/{name}/check
    ├── register.php      # bulk /domain/{name}/create (quotes price via /check)
    ├── renew.php         # bulk /domain/{name}/renew (uses /info ETS + /check price)
    ├── transfer.php      # bulk /domain/{name}/transfer (TSV: domain<TAB>authCode)
    ├── ns.php            # bulk PATCH /domain/{name}/update/ns (TSV: domain + 1..13 ns)
    └── privacy.php       # bulk PATCH /domain/{name}/update/whois_privacy
```

## Architecture details (as built)

### `NicnamesClient`

```php
public function __construct(private readonly HttpTransport $transport) {}
```

API key is supplied to `HttpTransport`; the client itself has no auth state. Every request just carries the `x-api-key`
header set by the transport.

| Method                                                        | HTTP                                   | Returns                   |
|---------------------------------------------------------------|----------------------------------------|---------------------------|
| `listDomains(?ListParams)`                                    | GET /domain                            | `DomainList`              |
| `infoDomain(string)`                                          | GET /domain/{name}/info                | `OrderDomainModel`        |
| `checkDomain(string)`                                         | GET /domain/{name}/check               | `DomainCheckResult`       |
| `createDomain(string, CreateDomainRequest)`                   | POST .../create                        | `DomainOperationResult`   |
| `transferDomain(string, TransferDomainRequest)`               | POST .../transfer                      | `DomainOperationResult`   |
| `renewDomain(string, RenewDomainRequest)`                     | POST .../renew                         | `DomainOperationResult`   |
| `restoreDomain(string, RestoreDomainRequest)`                 | POST .../restore                       | `DomainOperationResult`   |
| `updateDomainNameServers(string, string[])`                   | PATCH .../update/ns                    | `DomainOperationResult`   |
| `updateDomainWhoisPrivacy(string, UpdateWhoisPrivacyRequest)` | PATCH .../update/whois_privacy         | `DomainOperationResult`   |
| `resendRegistrantEmailVerification(string)`                   | POST .../registrant_email_verification | `EmailVerificationStatus` |
| `getRegistrantEmailVerificationStatus(string)`                | GET .../registrant_email_verification  | `EmailVerificationStatus` |
| `listContacts(?ListParams)`                                   | GET /contact                           | `ContactList`             |
| `createContact(CreateContactRequest)`                         | POST /contact                          | `ContactModel`            |
| `infoContact(string)`                                         | GET /contact/{id}/info                 | `ContactModel`            |

A private `domainOperation()` helper centralises the 200/201-vs-202 branching for the seven methods that return
`DomainOperationResult`.

### `HttpTransport` + `TransportResponse`

```php
public function __construct(
    HttpClientInterface $httpClient,
    string $apiKey,
    string $baseUrl = 'https://api.nicnames.com/2',
    ?LoggerInterface $logger = null,
) {}

public function request(string $method, string $path, ?array $body = null): TransportResponse
```

- Adds `x-api-key` and `Accept: application/json` headers on every call.
- Wraps Symfony HttpClient errors in `TransportException`.
- On 4XX/5XX, parses the JSON body as `ErrorModel` (`code`, `message`, `traceId`) and throws the exception class from
  the code map below.
- Returns `TransportResponse(int $status, array $body)`; the client uses `->isAccepted()` (status === 202) to branch
  into `DomainOperationResult::fromJob()`.

### `DomainOperationResult` (the 202 wrapper)

```php
final readonly class DomainOperationResult {
    public function __construct(
        public ?OrderDomainModel $order = null,
        public ?string $jobId = null,
    ) {}
    public static function fromOrder(OrderDomainModel $o): self { return new self(order: $o); }
    public static function fromJob(string $jobId): self          { return new self(jobId: $jobId); }
    public function isAsync(): bool { return $this->jobId !== null; }
}
```

### Exception mapping (codes from `doc/openapi.json` description, lines 22–63)

```php
match ($errorCode) {
    442001 => RemoteException::class,
    442002 => ApiException::class,
    442003 => ForbiddenException::class,
    442006 => UnknownStatusException::class,
    442007 => NotFoundException::class,
    442008 => StatusProhibitedException::class,
    442009 => InvalidParamPolicyException::class,
    442010 => InvalidParamValueException::class,
    442011 => ParamRequiredException::class,
    442012 => UnauthorizedException::class,
    default => NicnamesException::class,
};
```

### Webhook helpers

`WebhookVerifier::isValid(string $payload, string $timestamp, string $signature): bool`

- Computes `hash_hmac('sha256', $payload . $timestamp, $secret)` and compares with `hash_equals`.
- Companion `sign(payload, timestamp): string` is used by the test suite to produce known-good vectors.

`WebhookEventFactory::fromArray(array): WebhookEvent` and `fromJson(string): WebhookEvent`

- `eventType === 'job_result'` → `WebhookJobResultEvent` (jobId + code)
- `eventType === 'domain_status'` → `WebhookDomainStatusEvent` (eventData = `OrderDomainModel`)
- anything else → base `WebhookEvent` (id, type, message)

### DTO style

- Response DTOs are `final readonly` with `createFromArray()` factories; `(string)`/`(int)`/`(bool)` coercion and
  `?? default` fallbacks.
- Request DTOs are `final readonly` with `toArray()` (omits null fields where the API treats absence as "leave
  unchanged").
- `OperationModel` and `PeriodUnitModel` are `final` non-readonly classes with `public const string` constants —
  explicit enum-like API without committing to a backed enum.
- `OrderModel` / `OrderDomainModel` are the only inheritance pair: `OrderModel` is `readonly` (non-final) so the
  discriminator can grow if Nicnames adds new order subtypes; `OrderDomainModel` is `final readonly` and overrides
  `createFromArray`.
- `WebhookEvent` / `WebhookJobResultEvent` / `WebhookDomainStatusEvent` follow the same shape.
- `OrderDomainModel::createFromArray()` reads `type` and the nested `domain` object; `DomainList::createFromArray()`
  maps every entry through it.

### Config files

- `composer.json` — `php >=8.4`, `psr/log ^3.0`, `symfony/http-client ^6.4 || ^7.4 || ^8.0`; dev `phpstan/phpstan ^2.1`,
  `phpunit/phpunit ^13.0`, `monolog/monolog ^3.0`; PSR-4 `Luchaninov\\NicnamesClient\\` → `src/`,
  `Luchaninov\\NicnamesClient\\Tests\\` → `tests/`.
- `phpstan.neon` — level 5, paths `src` + `tests`.
- `phpunit.xml.dist` — `failOnRisky="true" failOnWarning="true"`.
- `.editorconfig` — UTF-8, LF, 4 spaces.
- `.gitignore` — `vendor/`, `composer.lock`, `.env.local`, `var/`, `.phpunit.result.cache`, `.idea/`.

## Tests (80 tests, 262 assertions, 100% line/method/class coverage — all green)

- `HttpTransportTest`
    - `x-api-key` header present on every request
    - 200 returns body, 202 sets `isAccepted()`
    - DataProvider over each error code (442001–442012 and one fallback) → correct exception class, correct `traceId`
    - 5XX without JSON → `TransportException`
- `NicnamesClientTest`
    - `infoDomain` decodes nested `OrderDomainModel`/`DomainModel`
    - `listDomains` decodes pagination
    - `checkDomain` decodes `PriceModel[]`
    - `createDomain` returns `DomainOperationResult` with `->order` set on 201 and with `->jobId` set on 202
    - `updateDomainNameServers` sends PATCH and `{"ns": [...]}`
    - `updateDomainWhoisPrivacy` omits null fields
    - `createContact` round-trip, `listContacts` pagination, `getRegistrantEmailVerificationStatus`
- `Webhook/WebhookVerifierTest` — known-good vector passes, tampered payload fails, wrong secret fails, `sign()` returns
  lowercase 64-char hex
- `Webhook/WebhookEventFactoryTest` — `job_result` → `WebhookJobResultEvent`, `domain_status` →
  `WebhookDomainStatusEvent` (with nested domain decoded), unknown type → base `WebhookEvent`, `fromJson()` parses
- `Dto/OrderDomainModelTest` — full + minimal decode (without nested `domain`)
- `Dto/ContactModelTest` — full decode, optional fields nullable
- `Dto/DomainOperationResultTest` — `fromOrder` / `fromJob` factories

## Examples

`_lib.php` exposes `loadEnv`, `getArg`, `requireArg`, `readInputLines`, `applyResume`, `hasFlag`, `createClient`.
`.env.local` keys: `NICNAMES_API_KEY`, optional `NICNAMES_BASE_URL`, optional `NICNAMES_DEFAULT_REGISTRANT`.

Each bulk script reads input files (one domain per line, or TSV), iterates with progress logging to stderr, writes TSV
results to stdout, and supports `--resume <last>` for crash recovery.

## Verification (passed 2026-05-01)

1. `composer install` — succeeded.
2. `vendor/bin/phpstan analyze` — `[OK] No errors` at level 5. (Needs sandbox disabled because PHPStan binds a local TCP
   port for its worker pool.)
3. `vendor/bin/phpunit --coverage-text` — 80 tests, 262 assertions, all green; 100% line / method / class coverage (PCOV).
4. `php -l examples/*.php` — all parse cleanly.
5. **Pending:** real API smoke test — drop a `.env.local` with a real `NICNAMES_API_KEY` and run
   `php examples/test.php`.
6. **Pending:** end-to-end webhook round-trip against the actual Nicnames webhook delivery.
