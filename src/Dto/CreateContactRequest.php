<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class CreateContactRequest
{
    public function __construct(
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

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $a = [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'cc' => $this->cc,
            'pc' => $this->pc,
            'sp' => $this->sp,
            'city' => $this->city,
            'addr' => $this->addr,
            'email' => $this->email,
            'phone' => $this->phone,
            'phonePolicy' => $this->phonePolicy,
        ];
        if ($this->middleName !== null) {
            $a['middleName'] = $this->middleName;
        }
        if ($this->org !== null) {
            $a['org'] = $this->org;
        }
        if ($this->orgPhone !== null) {
            $a['orgPhone'] = $this->orgPhone;
        }
        if ($this->fax !== null) {
            $a['fax'] = $this->fax;
        }

        return $a;
    }
}
