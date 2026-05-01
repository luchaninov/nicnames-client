<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Dto;

use InvalidArgumentException;

final readonly class UpdateNameServersRequest
{
    public const int MAX_NAMESERVERS = 13;

    /** @var list<string> */
    public array $ns;

    /** @param string[] $ns */
    public function __construct(array $ns)
    {
        if (count($ns) > self::MAX_NAMESERVERS) {
            throw new InvalidArgumentException(sprintf(
                'Too many name servers: %d (max %d).',
                count($ns),
                self::MAX_NAMESERVERS,
            ));
        }
        $this->ns = array_values($ns);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['ns' => $this->ns];
    }
}
