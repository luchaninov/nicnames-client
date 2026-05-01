<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient;

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
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpTransport
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://api.nicnames.com/2',
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param array<string, mixed>|null $body
     */
    public function request(string $method, string $path, ?array $body = null): TransportResponse
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
        $options = [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ],
        ];
        if ($body !== null) {
            $options['json'] = $body;
        }

        $this->logger?->debug('Nicnames request', [
            'method' => $method,
            'url' => $url,
            'body' => $body,
        ]);

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $status = $response->getStatusCode();
            $data = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger?->error('Nicnames transport error', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new TransportException($e->getMessage(), 0, null, $e);
        }

        if ($status >= 400) {
            $this->logger?->error('Nicnames error response', [
                'method' => $method,
                'url' => $url,
                'status' => $status,
                'body' => $data,
            ]);
            $this->throwException($status, $data);
        }

        $this->logger?->debug('Nicnames response', [
            'method' => $method,
            'url' => $url,
            'status' => $status,
        ]);

        return new TransportResponse($status, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function throwException(int $httpStatus, array $data): never
    {
        $code = (int) ($data['code'] ?? 0);
        $message = (string) ($data['message'] ?? sprintf('HTTP %d', $httpStatus));
        $traceId = isset($data['traceId']) ? (string) $data['traceId'] : null;

        $exceptionClass = match ($code) {
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

        throw new $exceptionClass($message, $code, $traceId);
    }
}
