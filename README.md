# nicnames-client

PHP client for the [Nicnames](https://nicnames.com) domain registrar REST API v2.
Full API reference: [api.nicnames.com/docs/2/](https://api.nicnames.com/docs/2/).

## Requirements

- PHP 8.4+
- `symfony/http-client`

## Installation

```bash
composer require luchaninov/nicnames-client
```

## API key

Generate an API key at [nicnames.com/en/my/settings](https://nicnames.com/en/my/settings).
A paid Nicnames membership is required to access the API.

## Usage

```php
use Luchaninov\NicnamesClient\HttpTransport;
use Luchaninov\NicnamesClient\NicnamesClient;
use Symfony\Component\HttpClient\HttpClient;

$transport = new HttpTransport(HttpClient::create(), apiKey: 'YOUR-API-KEY');
$client = new NicnamesClient($transport);

$domains = $client->listDomains();
foreach ($domains->list as $order) {
    echo $order->domain?->name . ' — ' . implode(',', $order->status) . PHP_EOL;
}
```

### Automatic retries

`HttpTransport` accepts any `Symfony\Contracts\HttpClient\HttpClientInterface`,
so transient failures (5xx, network blips) can be handled by wrapping with
Symfony's `RetryableHttpClient`:

```php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;

$http = new RetryableHttpClient(HttpClient::create(), maxRetries: 3);
$transport = new HttpTransport($http, apiKey: 'YOUR-API-KEY');
```

### Domain info & availability

```php
$order = $client->infoDomain('example.com');
echo $order->domain?->name;

$check = $client->checkDomain('example.com');
// $check->availableFor: NONE|CREATE|TRANSFER|RENEW|RESTORE|UPDATE
// $check->tier:         REGULAR|PREMIUM|UNKNOWN
// $check->price:        PriceModel[] — one entry per period
```

### Create a domain

Operations that the API may complete inline (200/201) **or** schedule as a background
job (202) return a `DomainOperationResult` wrapper:

```php
use Luchaninov\NicnamesClient\Dto\CreateDomainRequest;
use Luchaninov\NicnamesClient\Dto\OperationModel;
use Luchaninov\NicnamesClient\Dto\PeriodModel;
use Luchaninov\NicnamesClient\Dto\PeriodUnitModel;
use Luchaninov\NicnamesClient\Dto\PriceModel;

$price = new PriceModel(
    amt: 12.34,
    ccy: 840,
    op: OperationModel::CREATE,
    period: new PeriodModel(PeriodUnitModel::YEARS, 1),
);

$result = $client->createDomain('example.com', new CreateDomainRequest(
    price: $price,
    registrant: 'c987654321',
));

if ($result->isAsync()) {
    echo "Scheduled as job {$result->jobId}\n";
} else {
    echo "Created order {$result->order->oid}\n";
}
```

The same wrapper is returned by `transferDomain()`, `renewDomain()`, `restoreDomain()`,
`updateDomainNameServers()`, and `updateDomainWhoisPrivacy()`.

### Contacts

```php
use Luchaninov\NicnamesClient\Dto\CreateContactRequest;

$contact = $client->createContact(new CreateContactRequest(
    firstName: 'John',
    lastName: 'Doe',
    cc: 'us',
    pc: '62704',
    sp: 'IL',
    city: 'Springfield',
    addr: '123 Main Street',
    email: 'john.doe@example.com',
    phone: '+15551234567',
    phonePolicy: true,
));

$contacts = $client->listContacts();
$one = $client->infoContact('c987654321');
```

### Update nameservers / WHOIS privacy

```php
use Luchaninov\NicnamesClient\Dto\UpdateNameServersRequest;
use Luchaninov\NicnamesClient\Dto\UpdateWhoisPrivacyRequest;

$client->updateDomainNameServers(
    'example.com',
    new UpdateNameServersRequest(['ns1.example.com', 'ns2.example.com']),
);
$client->updateDomainWhoisPrivacy('example.com', new UpdateWhoisPrivacyRequest(
    registrant: UpdateWhoisPrivacyRequest::ENABLE,
));
```

### Webhooks

The API delivers asynchronous results and events as `application/x-www-form-urlencoded`
POSTs containing `object` (JSON), `timestamp`, and `signature` fields. The simplest
integration uses `WebhookHandler`, which verifies the HMAC-SHA256 signature, checks
that the timestamp is fresh (default 5 min anti-replay window), and decodes the JSON
into a typed event in one call:

```php
use Luchaninov\NicnamesClient\Dto\WebhookJobResultEvent;
use Luchaninov\NicnamesClient\Webhook\WebhookException;
use Luchaninov\NicnamesClient\Webhook\WebhookHandler;
use Luchaninov\NicnamesClient\Webhook\WebhookVerifier;

$handler = new WebhookHandler(new WebhookVerifier(secret: 'YOUR-WEBHOOK-SECRET'));

try {
    $event = $handler->handle($_POST);
} catch (WebhookException) {
    http_response_code(401);
    exit;
}

if ($event instanceof WebhookJobResultEvent) {
    // $event->jobId, $event->code (e.g. 441000 SUCCESS, 442xxx error codes)
}
```

If you need finer control, `WebhookVerifier::isValid()` and `WebhookEventFactory::fromJson()`
are public and composable.

## Error handling

Every API result code maps to a typed exception:

| Code     | Exception                     |
|----------|-------------------------------|
| `442001` | `RemoteException`             |
| `442002` | `ApiException`                |
| `442003` | `ForbiddenException`          |
| `442006` | `UnknownStatusException`      |
| `442007` | `NotFoundException`           |
| `442008` | `StatusProhibitedException`   |
| `442009` | `InvalidParamPolicyException` |
| `442010` | `InvalidParamValueException`  |
| `442011` | `ParamRequiredException`      |
| `442012` | `UnauthorizedException`       |
| other    | `NicnamesException`           |

All exceptions carry the API trace id:

```php
use Luchaninov\NicnamesClient\Exception\NicnamesException;

try {
    $client->createDomain('bad', $request);
} catch (NicnamesException $e) {
    $e->getCode();   // 442010
    $e->getMessage(); // 'Invalid domain name.'
    $e->traceId;      // 'c4f9d5b3-...'
}
```

HTTP / network failures throw `TransportException`. A 2xx response that doesn't conform to
the spec (e.g. an HTTP 202 without a `jobId`) throws `MalformedResponseException`.

Webhook validation failures (missing fields, bad signature, stale timestamp, malformed JSON)
throw `Webhook\WebhookException` — a separate hierarchy from `NicnamesException`, since they
originate in your inbound traffic, not from the API.

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyze
```

### Manual smoke test

Copy `.env.local.example` to `.env.local` (gitignored) and fill in your API key.
Then run:

```bash
php examples/test.php
```

### Bulk scripts

All scripts read from `--input <file>`, support `--resume`, and emit TSV to stdout.

```bash
php examples/check.php    --input domains.txt > availability.tsv
php examples/register.php --input domains.txt --registrant c987654321 --term 1 > registered.tsv
php examples/renew.php    --input domains.txt --term 1 > renewed.tsv
php examples/transfer.php --input transfers.tsv --registrant c987654321 --term 1 > transferred.tsv
php examples/ns.php       --input ns.tsv > ns-results.tsv
php examples/privacy.php  --input domains.txt --on > privacy-on.tsv
php examples/privacy.php  --input domains.txt --off --apply-to-all > privacy-off.tsv

# Resume any script after interruption (skip already-processed entries)
php examples/register.php --input domains.txt --registrant c987654321 --resume last-done.com >> registered.tsv
```

## License

MIT


## Help Ukraine

If you find this project useful, please consider supporting Ukraine:
🇺🇦 [Donate](https://commission.europa.eu/topics/eu-solidarity-ukraine/donate_en)
