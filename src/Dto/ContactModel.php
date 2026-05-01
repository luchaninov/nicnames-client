<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class ContactModel
{
    public function __construct(
        public string $contactId,
        public string $firstName,
        public string $lastName,
        public string $cc,
        public string $pc,
        public string $sp,
        public string $city,
        public string $addr,
        public string $email,
        public string $phone,
        public bool $phonePolicy,
        public ?string $middleName = null,
        public ?string $org = null,
        public ?string $orgPhone = null,
        public ?string $fax = null,
    ) {
    }

    /** @param array<string, mixed> $a */
    public static function createFromArray(array $a): self
    {
        return new self(
            contactId: (string) ($a['contactId'] ?? ''),
            firstName: (string) ($a['firstName'] ?? ''),
            lastName: (string) ($a['lastName'] ?? ''),
            cc: (string) ($a['cc'] ?? ''),
            pc: (string) ($a['pc'] ?? ''),
            sp: (string) ($a['sp'] ?? ''),
            city: (string) ($a['city'] ?? ''),
            addr: (string) ($a['addr'] ?? ''),
            email: (string) ($a['email'] ?? ''),
            phone: (string) ($a['phone'] ?? ''),
            phonePolicy: (bool) ($a['phonePolicy'] ?? false),
            middleName: isset($a['middleName']) ? (string) $a['middleName'] : null,
            org: isset($a['org']) ? (string) $a['org'] : null,
            orgPhone: isset($a['orgPhone']) ? (string) $a['orgPhone'] : null,
            fax: isset($a['fax']) ? (string) $a['fax'] : null,
        );
    }
}
