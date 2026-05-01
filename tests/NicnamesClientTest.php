<?php /** @noinspection JsonEncodingApiUsageInspection */

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests;

use Luchaninov\NicnamesClient\Dto\ContactList;
use Luchaninov\NicnamesClient\Dto\ContactModel;
use Luchaninov\NicnamesClient\Dto\CreateContactRequest;
use Luchaninov\NicnamesClient\Dto\CreateDomainRequest;
use Luchaninov\NicnamesClient\Dto\DomainCheckResult;
use Luchaninov\NicnamesClient\Dto\DomainList;
use Luchaninov\NicnamesClient\Dto\ListParams;
use Luchaninov\NicnamesClient\Dto\OperationModel;
use Luchaninov\NicnamesClient\Dto\OrderDomainModel;
use Luchaninov\NicnamesClient\Dto\PeriodModel;
use Luchaninov\NicnamesClient\Dto\PeriodUnitModel;
use Luchaninov\NicnamesClient\Dto\PriceModel;
use Luchaninov\NicnamesClient\Dto\RenewDomainRequest;
use Luchaninov\NicnamesClient\Dto\RestoreDomainRequest;
use Luchaninov\NicnamesClient\Dto\TierModel;
use Luchaninov\NicnamesClient\Dto\TransferDomainRequest;
use Luchaninov\NicnamesClient\Dto\UpdateNameServersRequest;
use Luchaninov\NicnamesClient\Dto\UpdateWhoisPrivacyRequest;
use Luchaninov\NicnamesClient\Exception\MalformedResponseException;
use Luchaninov\NicnamesClient\HttpTransport;
use Luchaninov\NicnamesClient\NicnamesClient;
use Luchaninov\NicnamesClient\NicnamesClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class NicnamesClientTest extends TestCase
{
    /**
     * @param list<MockResponse|callable> $responses
     */
    private function makeClient(array $responses): NicnamesClient
    {
        $mock = new MockHttpClient($responses);
        $transport = new HttpTransport($mock, 'TEST-KEY');

        return new NicnamesClient($transport);
    }

    public function testInfoDomainDecodesNestedDomain(): void
    {
        $client = $this->makeClient([
            new MockResponse(
                json_encode([
                    'oid' => 'o54321',
                    'type' => 'domain',
                    'status' => ['active'],
                    'cts' => 1577836800,
                    'uts' => 1577836800,
                    'ets' => 1609459200,
                    'domain' => [
                        'name' => 'example.com',
                        'registrant' => 'c987654321',
                        'admin' => 'c987654321',
                        'tech' => 'c987654321',
                        'billing' => 'c987654321',
                        'ns' => ['ns1.example.com', 'ns2.example.com'],
                    ],
                ], JSON_THROW_ON_ERROR),
                ['http_code' => 200],
            ),
        ]);

        $order = $client->infoDomain('example.com');
        self::assertInstanceOf(OrderDomainModel::class, $order);
        self::assertSame('o54321', $order->oid);
        self::assertSame('domain', $order->type);
        self::assertSame(['active'], $order->status);
        self::assertNotNull($order->domain);
        self::assertSame('example.com', $order->domain->name);
        self::assertSame(['ns1.example.com', 'ns2.example.com'], $order->domain->ns);
    }

    public function testListDomainsDecodesPagination(): void
    {
        $client = $this->makeClient([
            new MockResponse(
                json_encode([
                    'total' => 2,
                    'list' => [
                        ['oid' => 'o1', 'type' => 'domain', 'status' => ['active'], 'cts' => 1, 'uts' => 1],
                        ['oid' => 'o2', 'type' => 'domain', 'status' => ['expired'], 'cts' => 2, 'uts' => 2],
                    ],
                ], JSON_THROW_ON_ERROR),
                ['http_code' => 200],
            ),
        ]);

        $list = $client->listDomains();
        self::assertInstanceOf(DomainList::class, $list);
        self::assertSame(2, $list->total);
        self::assertCount(2, $list->list);
        self::assertSame('o1', $list->list[0]->oid);
    }

    public function testCheckDomainDecodesPriceList(): void
    {
        $client = $this->makeClient([
            new MockResponse(
                json_encode([
                    'domainName' => 'example.com',
                    'availableFor' => 'CREATE',
                    'tier' => 'REGULAR',
                    'price' => [
                        ['amt' => 12.34, 'ccy' => 840, 'op' => 'CREATE', 'period' => ['unit' => 'YEARS', 'value' => 1]],
                        ['amt' => 23.45, 'ccy' => 840, 'op' => 'CREATE', 'period' => ['unit' => 'YEARS', 'value' => 2]],
                    ],
                ], JSON_THROW_ON_ERROR),
                ['http_code' => 200],
            ),
        ]);

        $check = $client->checkDomain('example.com');
        self::assertInstanceOf(DomainCheckResult::class, $check);
        self::assertSame(OperationModel::CREATE, $check->availableFor);
        self::assertSame(TierModel::REGULAR, $check->tier);
        self::assertCount(2, $check->price);
        self::assertSame(12.34, $check->price[0]->amt);
        self::assertSame(1, $check->price[0]->period->value);
    }

    public function testCreateDomainSyncReturnsOrder(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = [
                'method' => $method,
                'url' => $url,
                'headers' => $options['headers'] ?? [],
                'body' => json_decode($options['body'] ?? '{}', true),
            ];

            return new MockResponse(
                json_encode([
                    'oid' => 'o-new',
                    'type' => 'domain',
                    'status' => ['new'],
                    'cts' => 100,
                    'uts' => 100,
                    'domain' => ['name' => 'example.com', 'registrant' => 'c987654321'],
                ], JSON_THROW_ON_ERROR),
                ['http_code' => 201],
            );
        });
        $client = new NicnamesClient(new HttpTransport($mock, 'KEY'));

        $price = new PriceModel(12.34, 840, OperationModel::CREATE, new PeriodModel(PeriodUnitModel::YEARS, 1));
        $request = new CreateDomainRequest($price, 'c987654321');
        $result = $client->createDomain('example.com', $request);

        self::assertFalse($result->isAsync());
        self::assertNull($result->jobId);
        self::assertNotNull($result->order);
        self::assertSame('o-new', $result->order->oid);
        self::assertSame('POST', $captured['method']);
        self::assertStringEndsWith('/domain/example.com/create', $captured['url']);
        self::assertSame('c987654321', $captured['body']['registrant']);
        self::assertContains('x-api-key: KEY', $captured['headers']);
    }

    public function testCreateDomainAsyncReturnsJobId(): void
    {
        $client = $this->makeClient([
            new MockResponse(json_encode(['jobId' => 'JOB-XYZ'], JSON_THROW_ON_ERROR), ['http_code' => 202]),
        ]);

        $price = new PriceModel(12.34, 840, OperationModel::CREATE, new PeriodModel(PeriodUnitModel::YEARS, 1));
        $request = new CreateDomainRequest($price, 'c987654321');
        $result = $client->createDomain('example.com', $request);

        self::assertTrue($result->isAsync());
        self::assertSame('JOB-XYZ', $result->jobId);
        self::assertNull($result->order);
    }

    public function testCreateDomain202WithoutJobIdThrowsMalformedResponse(): void
    {
        $client = $this->makeClient([
            new MockResponse(json_encode([], JSON_THROW_ON_ERROR), ['http_code' => 202]),
        ]);

        $price = new PriceModel(12.34, 840, OperationModel::CREATE, new PeriodModel(PeriodUnitModel::YEARS, 1));
        $request = new CreateDomainRequest($price, 'c987654321');

        $this->expectException(MalformedResponseException::class);
        $this->expectExceptionMessageMatches('/jobId/');
        $client->createDomain('example.com', $request);
    }

    public function testCreateDomain202WithEmptyJobIdThrowsMalformedResponse(): void
    {
        $client = $this->makeClient([
            new MockResponse(json_encode(['jobId' => ''], JSON_THROW_ON_ERROR), ['http_code' => 202]),
        ]);

        $price = new PriceModel(12.34, 840, OperationModel::CREATE, new PeriodModel(PeriodUnitModel::YEARS, 1));
        $request = new CreateDomainRequest($price, 'c987654321');

        $this->expectException(MalformedResponseException::class);
        $client->createDomain('example.com', $request);
    }

    public function testUpdateNameServersSendsPatchAndArray(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['method' => $method, 'body' => json_decode($options['body'] ?? '{}', true)];

            return new MockResponse(
                json_encode(['oid' => 'o1', 'type' => 'domain', 'status' => ['pendingUpdate'], 'cts' => 1, 'uts' => 1], JSON_THROW_ON_ERROR),
                ['http_code' => 200],
            );
        });
        $client = new NicnamesClient(new HttpTransport($mock, 'KEY'));

        $result = $client->updateDomainNameServers(
            'example.com',
            new UpdateNameServersRequest(['ns1.test.', 'ns2.test.']),
        );
        self::assertSame('PATCH', $captured['method']);
        self::assertSame(['ns1.test.', 'ns2.test.'], $captured['body']['ns']);
        self::assertNotNull($result->order);
    }

    public function testUpdateWhoisPrivacyOmitsNullFields(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['body' => json_decode($options['body'] ?? '{}', true)];

            return new MockResponse(
                json_encode(['oid' => 'o1', 'type' => 'domain', 'status' => [], 'cts' => 1, 'uts' => 1], JSON_THROW_ON_ERROR),
                ['http_code' => 200],
            );
        });
        $client = new NicnamesClient(new HttpTransport($mock, 'KEY'));

        $client->updateDomainWhoisPrivacy('example.com', new UpdateWhoisPrivacyRequest(
            registrant: UpdateWhoisPrivacyRequest::ENABLE,
        ));
        self::assertSame(['registrant' => 'enable'], $captured['body']);
    }

    public function testCreateContactReturnsContactWithId(): void
    {
        $client = $this->makeClient([
            new MockResponse(
                json_encode([
                    'contactId' => 'c987654321',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'cc' => 'us',
                    'pc' => '62704',
                    'sp' => 'IL',
                    'city' => 'Springfield',
                    'addr' => '123 Main Street',
                    'email' => 'john.doe@example.com',
                    'phone' => '+15551234567',
                    'phonePolicy' => true,
                ], JSON_THROW_ON_ERROR),
                ['http_code' => 201],
            ),
        ]);

        $request = new CreateContactRequest(
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
        );
        $contact = $client->createContact($request);
        self::assertInstanceOf(ContactModel::class, $contact);
        self::assertSame('c987654321', $contact->contactId);
        self::assertTrue($contact->phonePolicy);
    }

    public function testListContactsDecodesPagination(): void
    {
        $client = $this->makeClient([
            new MockResponse(
                json_encode([
                    'total' => 1,
                    'list' => [[
                        'contactId' => 'c1',
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                        'cc' => 'us',
                        'pc' => '62704',
                        'sp' => 'IL',
                        'city' => 'Springfield',
                        'addr' => '123',
                        'email' => 'a@b.c',
                        'phone' => '+1',
                        'phonePolicy' => true,
                    ]],
                ], JSON_THROW_ON_ERROR),
                ['http_code' => 200],
            ),
        ]);

        $list = $client->listContacts();
        self::assertInstanceOf(ContactList::class, $list);
        self::assertSame(1, $list->total);
        self::assertSame('c1', $list->list[0]->contactId);
    }

    public function testEmailVerificationStatus(): void
    {
        $client = $this->makeClient([
            new MockResponse(json_encode(['code' => 123456, 'email' => 'a@b.c'], JSON_THROW_ON_ERROR), ['http_code' => 200]),
        ]);

        $status = $client->getRegistrantEmailVerificationStatus('example.com');
        self::assertSame(123456, $status->code);
        self::assertSame('a@b.c', $status->email);
    }

    public function testTransferDomainSendsAuthCodeAndRegistrant(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = [
                'method' => $method,
                'url' => $url,
                'body' => json_decode($options['body'] ?? '{}', true),
            ];

            return new MockResponse(
                json_encode(['oid' => 'o-t', 'type' => 'domain', 'status' => ['new'], 'cts' => 1, 'uts' => 1], JSON_THROW_ON_ERROR),
                ['http_code' => 200],
            );
        });
        $client = new NicnamesClient(new HttpTransport($mock, 'KEY'));

        $price = new PriceModel(12.34, 840, OperationModel::TRANSFER, new PeriodModel(PeriodUnitModel::YEARS, 1));
        $request = new TransferDomainRequest($price, 'c987654321', 'xGi%8KFk3wQV');
        $result = $client->transferDomain('example.com', $request);

        self::assertSame('POST', $captured['method']);
        self::assertStringEndsWith('/domain/example.com/transfer', $captured['url']);
        self::assertSame('xGi%8KFk3wQV', $captured['body']['authCode']);
        self::assertSame('c987654321', $captured['body']['registrant']);
        self::assertNotNull($result->order);
        self::assertSame('o-t', $result->order->oid);
    }

    public function testRenewDomainSendsCurrentEts(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = [
                'method' => $method,
                'url' => $url,
                'body' => json_decode($options['body'] ?? '{}', true),
            ];

            return new MockResponse(json_encode(['jobId' => 'JOB-RENEW'], JSON_THROW_ON_ERROR), ['http_code' => 202]);
        });
        $client = new NicnamesClient(new HttpTransport($mock, 'KEY'));

        $price = new PriceModel(12.34, 840, OperationModel::RENEW, new PeriodModel(PeriodUnitModel::YEARS, 1));
        $request = new RenewDomainRequest($price, currentETS: 1577836800);
        $result = $client->renewDomain('example.com', $request);

        self::assertStringEndsWith('/domain/example.com/renew', $captured['url']);
        self::assertSame(1577836800, $captured['body']['currentETS']);
        self::assertSame('RENEW', $captured['body']['price']['op']);
        self::assertTrue($result->isAsync());
        self::assertSame('JOB-RENEW', $result->jobId);
    }

    public function testRestoreDomainPostsPrice(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['url' => $url, 'body' => json_decode($options['body'] ?? '{}', true)];

            return new MockResponse(
                json_encode(['oid' => 'o-r', 'type' => 'domain', 'status' => ['active'], 'cts' => 1, 'uts' => 1], JSON_THROW_ON_ERROR),
                ['http_code' => 200],
            );
        });
        $client = new NicnamesClient(new HttpTransport($mock, 'KEY'));

        $price = new PriceModel(99.99, 840, OperationModel::RESTORE, new PeriodModel(PeriodUnitModel::YEARS, 1));
        $request = new RestoreDomainRequest($price);
        $result = $client->restoreDomain('example.com', $request);

        self::assertStringEndsWith('/domain/example.com/restore', $captured['url']);
        self::assertSame(99.99, $captured['body']['price']['amt']);
        self::assertSame('RESTORE', $captured['body']['price']['op']);
        self::assertNotNull($result->order);
    }

    public function testResendRegistrantEmailVerificationPostsAndReturnsCode(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['method' => $method, 'url' => $url];

            return new MockResponse(
                json_encode(['code' => 441001, 'email' => 'verify@example.com'], JSON_THROW_ON_ERROR),
                ['http_code' => 202],
            );
        });
        $client = new NicnamesClient(new HttpTransport($mock, 'KEY'));

        $status = $client->resendRegistrantEmailVerification('example.com');
        self::assertSame('POST', $captured['method']);
        self::assertStringEndsWith('/domain/example.com/registrant_email_verification', $captured['url']);
        self::assertSame(441001, $status->code);
        self::assertSame('verify@example.com', $status->email);
    }

    public function testInfoContact(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['method' => $method, 'url' => $url];

            return new MockResponse(
                json_encode([
                    'contactId' => 'c987654321',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'cc' => 'us',
                    'pc' => '62704',
                    'sp' => 'IL',
                    'city' => 'Springfield',
                    'addr' => '123',
                    'email' => 'a@b.c',
                    'phone' => '+1',
                    'phonePolicy' => true,
                ], JSON_THROW_ON_ERROR),
                ['http_code' => 200],
            );
        });
        $client = new NicnamesClient(new HttpTransport($mock, 'KEY'));

        $contact = $client->infoContact('c987654321');
        self::assertSame('GET', $captured['method']);
        self::assertStringEndsWith('/contact/c987654321/info', $captured['url']);
        self::assertSame('c987654321', $contact->contactId);
    }

    public function testListDomainsForwardsListParams(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['body' => json_decode($options['body'] ?? '{}', true)];

            return new MockResponse(json_encode(['total' => 0, 'list' => []], JSON_THROW_ON_ERROR), ['http_code' => 200]);
        });
        $client = new NicnamesClient(new HttpTransport($mock, 'KEY'));

        $client->listDomains(new ListParams(pgn: 3, pgl: 50, filter: "email = 'x@y.z'"));
        self::assertSame(3, $captured['body']['pgn']);
        self::assertSame(50, $captured['body']['pgl']);
        self::assertSame("email = 'x@y.z'", $captured['body']['filter']);
    }

    public function testListDomainsWithoutParamsSendsNoBody(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['hasBody' => isset($options['body'])];

            return new MockResponse(json_encode(['total' => 0, 'list' => []], JSON_THROW_ON_ERROR), ['http_code' => 200]);
        });
        $client = new NicnamesClient(new HttpTransport($mock, 'KEY'));
        $client->listDomains();
        self::assertFalse($captured['hasBody']);
    }

    public function testListContactsWithoutParamsSendsNoBody(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['hasBody' => isset($options['body'])];

            return new MockResponse(json_encode(['total' => 0, 'list' => []], JSON_THROW_ON_ERROR), ['http_code' => 200]);
        });
        $client = new NicnamesClient(new HttpTransport($mock, 'KEY'));
        $client->listContacts();
        self::assertFalse($captured['hasBody']);
    }

    public function testClientImplementsInterface(): void
    {
        $client = new NicnamesClient(new HttpTransport(new MockHttpClient(), 'KEY'));
        self::assertInstanceOf(NicnamesClientInterface::class, $client);
    }
}
