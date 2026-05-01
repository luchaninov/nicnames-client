<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class UpdateWhoisPrivacyRequest
{
    public const string ENABLE = 'enable';
    public const string DISABLE = 'disable';

    public function __construct(
        public ?string $registrant = null,
        public ?string $admin = null,
        public ?string $tech = null,
        public ?string $billing = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $a = [];
        if ($this->registrant !== null) {
            $a['registrant'] = $this->registrant;
        }
        if ($this->admin !== null) {
            $a['admin'] = $this->admin;
        }
        if ($this->tech !== null) {
            $a['tech'] = $this->tech;
        }
        if ($this->billing !== null) {
            $a['billing'] = $this->billing;
        }

        return $a;
    }
}
