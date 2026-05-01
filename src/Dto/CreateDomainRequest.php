<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

final readonly class CreateDomainRequest
{
    /** @param string[] $ns */
    public function __construct(
        public PriceModel $price,
        public string $registrant,
        public ?string $admin = null,
        public ?string $tech = null,
        public ?string $billing = null,
        public array $ns = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $a = [
            'price' => $this->price->toArray(),
            'registrant' => $this->registrant,
        ];
        if ($this->admin !== null) {
            $a['admin'] = $this->admin;
        }
        if ($this->tech !== null) {
            $a['tech'] = $this->tech;
        }
        if ($this->billing !== null) {
            $a['billing'] = $this->billing;
        }
        if ($this->ns !== []) {
            $a['ns'] = array_values($this->ns);
        }

        return $a;
    }
}
