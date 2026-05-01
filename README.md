# nicnames-client

PHP client for the [Nicnames](https://nicnames.com) domain registrar REST API v2.

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
use Luchaninov\NicnamesClient\Dto\UpdateWhoisPrivacyRequest;

$client->updateDomainNameServers('example.com', ['ns1.example.com', 'ns2.example.com']);
$client->updateDomainWhoisPrivacy('example.com', new UpdateWhoisPrivacyRequest(
    registrant: UpdateWhoisPrivacyRequest::ENABLE,
));
```

### Webhooks

The API delivers asynchronous results and events as `application/x-www-form-urlencoded`
POSTs containing `object` (JSON), `timestamp`, and `signature` fields. Verify the
HMAC-SHA256 signature and parse the event into a typed DTO:

```php
use Luchaninov\NicnamesClient\Dto\WebhookJobResultEvent;
use Luchaninov\NicnamesClient\Webhook\WebhookEventFactory;
use Luchaninov\NicnamesClient\Webhook\WebhookVerifier;

$payload   = $_POST['object'];
$timestamp = $_POST['timestamp'];
$signature = $_POST['signature'];

$verifier = new WebhookVerifier(secret: 'YOUR-WEBHOOK-SECRET');
if (!$verifier->isValid($payload, $timestamp, $signature)) {
    http_response_code(401);
    exit;
}

$event = WebhookEventFactory::fromJson($payload);
if ($event instanceof WebhookJobResultEvent) {
    // $event->jobId, $event->code (e.g. 441000 SUCCESS, 442xxx error codes)
}
```

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

HTTP / network failures throw `TransportException`.

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyze
```

### Manual smoke test

Create a `.env.local` in the project root (gitignored):

```ini
NICNAMES_API_KEY=your-api-key
NICNAMES_BASE_URL=https://api.nicnames.com/2  ; optional override
NICNAMES_DEFAULT_REGISTRANT=c987654321        ; required by register.php/transfer.php
```

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
