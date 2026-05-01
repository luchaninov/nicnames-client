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

interface NicnamesClientInterface
{
    public function listDomains(?ListParams $params = null): DomainList;

    public function infoDomain(string $domainName): OrderDomainModel;

    public function checkDomain(string $domainName): DomainCheckResult;

    public function createDomain(string $domainName, CreateDomainRequest $request): DomainOperationResult;

    public function transferDomain(string $domainName, TransferDomainRequest $request): DomainOperationResult;

    public function renewDomain(string $domainName, RenewDomainRequest $request): DomainOperationResult;

    public function restoreDomain(string $domainName, RestoreDomainRequest $request): DomainOperationResult;

    public function updateDomainNameServers(string $domainName, UpdateNameServersRequest $request): DomainOperationResult;

    public function updateDomainWhoisPrivacy(
        string $domainName,
        UpdateWhoisPrivacyRequest $request,
    ): DomainOperationResult;

    public function resendRegistrantEmailVerification(string $domainName): EmailVerificationStatus;

    public function getRegistrantEmailVerificationStatus(string $domainName): EmailVerificationStatus;

    public function listContacts(?ListParams $params = null): ContactList;

    public function createContact(CreateContactRequest $request): ContactModel;

    public function infoContact(string $contactId): ContactModel;
}
