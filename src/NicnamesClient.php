<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient;

use Luchaninov\NicnamesClient\Dto\ContactList;
use Luchaninov\NicnamesClient\Dto\ContactModel;
use Luchaninov\NicnamesClient\Dto\CreateContactRequest;
use Luchaninov\NicnamesClient\Dto\CreateDomainRequest;
use Luchaninov\NicnamesClient\Dto\DomainCheckResult;
use Luchaninov\NicnamesClient\Dto\DomainList;
use Luchaninov\NicnamesClient\Dto\DomainOperationResult;
use Luchaninov\NicnamesClient\Dto\EmailVerificationStatus;
use Luchaninov\NicnamesClient\Dto\ListParams;
use Luchaninov\NicnamesClient\Dto\OrderDomainModel;
use Luchaninov\NicnamesClient\Dto\RenewDomainRequest;
use Luchaninov\NicnamesClient\Dto\RestoreDomainRequest;
use Luchaninov\NicnamesClient\Dto\TransferDomainRequest;
use Luchaninov\NicnamesClient\Dto\UpdateNameServersRequest;
use Luchaninov\NicnamesClient\Dto\UpdateWhoisPrivacyRequest;
use Luchaninov\NicnamesClient\Exception\MalformedResponseException;

class NicnamesClient implements NicnamesClientInterface
{
    public function __construct(
        private readonly HttpTransport $transport,
    ) {
    }

    // --- Domains ---

    public function listDomains(?ListParams $params = null): DomainList
    {
        $response = $this->transport->request('GET', '/domain', $params?->toArray());

        return DomainList::createFromArray($response->body);
    }

    public function infoDomain(string $domainName): OrderDomainModel
    {
        $response = $this->transport->request('GET', sprintf('/domain/%s/info', rawurlencode($domainName)));

        return OrderDomainModel::createFromArray($response->body);
    }

    public function checkDomain(string $domainName): DomainCheckResult
    {
        $response = $this->transport->request('GET', sprintf('/domain/%s/check', rawurlencode($domainName)));

        return DomainCheckResult::createFromArray($response->body);
    }

    public function createDomain(string $domainName, CreateDomainRequest $request): DomainOperationResult
    {
        return $this->domainOperation('POST', sprintf('/domain/%s/create', rawurlencode($domainName)), $request->toArray());
    }

    public function transferDomain(string $domainName, TransferDomainRequest $request): DomainOperationResult
    {
        return $this->domainOperation('POST', sprintf('/domain/%s/transfer', rawurlencode($domainName)), $request->toArray());
    }

    public function renewDomain(string $domainName, RenewDomainRequest $request): DomainOperationResult
    {
        return $this->domainOperation('POST', sprintf('/domain/%s/renew', rawurlencode($domainName)), $request->toArray());
    }

    public function restoreDomain(string $domainName, RestoreDomainRequest $request): DomainOperationResult
    {
        return $this->domainOperation('POST', sprintf('/domain/%s/restore', rawurlencode($domainName)), $request->toArray());
    }

    public function updateDomainNameServers(string $domainName, UpdateNameServersRequest $request): DomainOperationResult
    {
        return $this->domainOperation(
            'PATCH',
            sprintf('/domain/%s/update/ns', rawurlencode($domainName)),
            $request->toArray(),
        );
    }

    public function updateDomainWhoisPrivacy(
        string $domainName,
        UpdateWhoisPrivacyRequest $request,
    ): DomainOperationResult {
        return $this->domainOperation(
            'PATCH',
            sprintf('/domain/%s/update/whois_privacy', rawurlencode($domainName)),
            $request->toArray(),
        );
    }

    public function resendRegistrantEmailVerification(string $domainName): EmailVerificationStatus
    {
        $response = $this->transport->request(
            'POST',
            sprintf('/domain/%s/registrant_email_verification', rawurlencode($domainName)),
        );

        return EmailVerificationStatus::createFromArray($response->body);
    }

    public function getRegistrantEmailVerificationStatus(string $domainName): EmailVerificationStatus
    {
        $response = $this->transport->request(
            'GET',
            sprintf('/domain/%s/registrant_email_verification', rawurlencode($domainName)),
        );

        return EmailVerificationStatus::createFromArray($response->body);
    }

    // --- Contacts ---

    public function listContacts(?ListParams $params = null): ContactList
    {
        $response = $this->transport->request('GET', '/contact', $params?->toArray());

        return ContactList::createFromArray($response->body);
    }

    public function createContact(CreateContactRequest $request): ContactModel
    {
        $response = $this->transport->request('POST', '/contact', $request->toArray());

        return ContactModel::createFromArray($response->body);
    }

    public function infoContact(string $contactId): ContactModel
    {
        $response = $this->transport->request('GET', sprintf('/contact/%s/info', rawurlencode($contactId)));

        return ContactModel::createFromArray($response->body);
    }

    // --- Internal ---

    /**
     * @param array<string, mixed>|null $body
     */
    private function domainOperation(string $method, string $path, ?array $body): DomainOperationResult
    {
        $response = $this->transport->request($method, $path, $body);

        if ($response->isAsync()) {
            $jobId = $response->body['jobId'] ?? null;
            if (!is_string($jobId) || $jobId === '') {
                throw new MalformedResponseException('202 Accepted response did not contain a non-empty jobId.');
            }

            return DomainOperationResult::fromJob($jobId);
        }

        return DomainOperationResult::fromOrder(OrderDomainModel::createFromArray($response->body));
    }
}
