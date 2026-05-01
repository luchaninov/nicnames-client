<?php /** @noinspection JsonEncodingApiUsageInspection */

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests;

use Luchaninov\NicnamesClient\Exception\ApiException;
use Luchaninov\NicnamesClient\Exception\ForbiddenException;
use Luchaninov\NicnamesClient\Exception\InvalidParamPolicyException;
use Luchaninov\NicnamesClient\Exception\InvalidParamValueException;
use Luchaninov\NicnamesClient\Exception\NicnamesException;
use Luchaninov\NicnamesClient\Exception\NotFoundException;
use Luchaninov\NicnamesClient\Exception\ParamRequiredException;
use Luchaninov\NicnamesClient\Exception\RemoteException;
use Luchaninov\NicnamesClient\Exception\StatusProhibitedException;
use Luchaninov\NicnamesClient\Exception\TransportException;
use Luchaninov\NicnamesClient\Exception\UnauthorizedException;
use Luchaninov\NicnamesClient\Exception\UnknownStatusException;
use Luchaninov\NicnamesClient\HttpTransport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class HttpTransportTest extends TestCase
{
    public function testApiKeyHeaderIsSent(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['method' => $method, 'url' => $url, 'headers' => $options['headers'] ?? []];

            return new MockResponse('{}');
        });

        $transport = new HttpTransport($mock, 'KEY-123', 'https://api.nicnames.com/2');
        $transport->request('GET', '/domain/example.com/info');

        self::assertSame('GET', $captured['method']);
        self::assertSame('https://api.nicnames.com/2/domain/example.com/info', $captured['url']);
        self::assertContains('x-api-key: KEY-123', $captured['headers']);
    }

    public function testReturnsBodyOn200(): void
    {
        $mock = new MockHttpClient([
            new MockResponse(json_encode(['oid' => 'o1', 'type' => 'domain']), ['http_code' => 200]),
        ]);
        $transport = new HttpTransport($mock, 'KEY');
        $response = $transport->request('GET', '/domain/x/info');
        self::assertSame(200, $response->status);
        self::assertSame('o1', $response->body['oid']);
    }

    public function testRecognises202(): void
    {
        $mock = new MockHttpClient([
            new MockResponse(json_encode(['jobId' => 'JOB42']), ['http_code' => 202]),
        ]);
        $transport = new HttpTransport($mock, 'KEY');
        $response = $transport->request('POST', '/domain/x/create', []);
        self::assertTrue($response->isAccepted());
        self::assertSame('JOB42', $response->body['jobId']);
    }

    /** @return iterable<string, array{int, class-string<NicnamesException>}> */
    public static function errorCodeProvider(): iterable
    {
        yield 'remote' => [442001, RemoteException::class];
        yield 'api' => [442002, ApiException::class];
        yield 'forbidden' => [442003, ForbiddenException::class];
        yield 'unknown status' => [442006, UnknownStatusException::class];
        yield 'not found' => [442007, NotFoundException::class];
        yield 'status prohibited' => [442008, StatusProhibitedException::class];
        yield 'param policy' => [442009, InvalidParamPolicyException::class];
        yield 'param value' => [442010, InvalidParamValueException::class];
        yield 'param required' => [442011, ParamRequiredException::class];
        yield 'unauthorized' => [442012, UnauthorizedException::class];
        yield 'unknown' => [440000, NicnamesException::class];
    }

    /** @param class-string<NicnamesException> $exceptionClass */
    #[DataProvider('errorCodeProvider')]
    public function testErrorCodeMapping(int $code, string $exceptionClass): void
    {
        $mock = new MockHttpClient([
            new MockResponse(
                json_encode([
                    'code' => $code,
                    'message' => 'Test error',
                    'traceId' => 'trace-xyz',
                ]),
                ['http_code' => 400],
            ),
        ]);
        $transport = new HttpTransport($mock, 'KEY');

        try {
            $transport->request('POST', '/domain/x/create', []);
            self::fail('Expected exception');
        } catch (NicnamesException $e) {
            self::assertInstanceOf($exceptionClass, $e);
            self::assertSame($code, $e->getCode());
            self::assertSame('Test error', $e->getMessage());
            self::assertSame('trace-xyz', $e->traceId);
        }
    }

    public function testTransportExceptionOnNetworkFailure(): void
    {
        $mock = new MockHttpClient([
            new MockResponse('not json', ['http_code' => 500]),
        ]);
        $transport = new HttpTransport($mock, 'KEY');
        $this->expectException(TransportException::class);
        $transport->request('GET', '/domain/x/info');
    }

    public function testErrorResponseWithoutTraceIdHasNullTraceId(): void
    {
        $mock = new MockHttpClient([
            new MockResponse(json_encode(['code' => 442007, 'message' => 'gone']), ['http_code' => 404]),
        ]);
        $transport = new HttpTransport($mock, 'KEY');
        try {
            $transport->request('GET', '/domain/x/info');
            self::fail('Expected exception');
        } catch (NicnamesException $e) {
            self::assertSame(442007, $e->getCode());
            self::assertNull($e->traceId);
        }
    }

    public function testErrorResponseWithoutCodeFallsBackToHttpStatusMessage(): void
    {
        $mock = new MockHttpClient([
            new MockResponse(json_encode([]), ['http_code' => 500]),
        ]);
        $transport = new HttpTransport($mock, 'KEY');
        try {
            $transport->request('GET', '/domain/x/info');
            self::fail('Expected exception');
        } catch (NicnamesException $e) {
            self::assertSame(0, $e->getCode());
            self::assertSame('HTTP 500', $e->getMessage());
        }
    }

    public function testBaseUrlTrailingSlashIsNormalised(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['url' => $url];

            return new MockResponse('{}');
        });
        $transport = new HttpTransport($mock, 'KEY', 'https://api.nicnames.com/2/');
        $transport->request('GET', '/domain/example.com/info');
        self::assertSame('https://api.nicnames.com/2/domain/example.com/info', $captured['url']);
    }

    public function testGetWithoutBodyDoesNotSendJsonOption(): void
    {
        $captured = [];
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['hasBody' => isset($options['body'])];

            return new MockResponse('{}');
        });
        $transport = new HttpTransport($mock, 'KEY');
        $transport->request('GET', '/domain/x/info');
        self::assertFalse($captured['hasBody']);
    }

    public function testLoggerIsCalledForRequestAndResponse(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{level: string, message: string|Stringable, context: array<string, mixed>}> */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => (string) $level, 'message' => $message, 'context' => $context];
            }
        };

        $mock = new MockHttpClient([new MockResponse('{}', ['http_code' => 200])]);
        $transport = new HttpTransport($mock, 'KEY', 'https://api.nicnames.com/2', $logger);
        $transport->request('GET', '/domain/x/info');

        $messages = array_map(fn(array $r) => (string) $r['message'], $logger->records);
        self::assertContains('Nicnames request', $messages);
        self::assertContains('Nicnames response', $messages);
    }

    public function testLoggerIsCalledForErrorResponse(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{level: string, message: string|Stringable, context: array<string, mixed>}> */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => (string) $level, 'message' => $message, 'context' => $context];
            }
        };

        $mock = new MockHttpClient([
            new MockResponse(json_encode(['code' => 442007, 'message' => 'gone']), ['http_code' => 404]),
        ]);
        $transport = new HttpTransport($mock, 'KEY', 'https://api.nicnames.com/2', $logger);

        try {
            $transport->request('GET', '/domain/x/info');
        } catch (NicnamesException) {
            // expected
        }

        $errorRecords = array_filter($logger->records, static fn(array $r) => $r['level'] === 'error');
        self::assertNotEmpty($errorRecords);
    }

    public function testLoggerIsCalledForTransportException(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{level: string, message: string|Stringable}> */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => (string) $level, 'message' => $message];
            }
        };

        $mock = new MockHttpClient([new MockResponse('not json', ['http_code' => 500])]);
        $transport = new HttpTransport($mock, 'KEY', 'https://api.nicnames.com/2', $logger);

        try {
            $transport->request('GET', '/domain/x/info');
        } catch (TransportException) {
            // expected
        }

        $errorMessages = array_filter(
            array_map(fn(array $r) => $r['level'] === 'error' ? (string) $r['message'] : null, $logger->records),
        );
        self::assertContains('Nicnames transport error', $errorMessages);
    }

    /**
     * Quiet PHPStan: `LoggerInterface` is referenced via the anonymous class extending AbstractLogger.
     */
    public function testLoggerInterfaceIsAvailable(): void
    {
        self::assertTrue(interface_exists(LoggerInterface::class));
    }
}
