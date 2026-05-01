<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests\Dto;

use InvalidArgumentException;
use Luchaninov\NicnamesClient\Dto\UpdateNameServersRequest;
use PHPUnit\Framework\TestCase;

class UpdateNameServersRequestTest extends TestCase
{
    public function testHappyPath(): void
    {
        $request = new UpdateNameServersRequest(['ns1.example.com', 'ns2.example.com']);
        self::assertSame(['ns' => ['ns1.example.com', 'ns2.example.com']], $request->toArray());
    }

    public function testEmptyArrayIsAccepted(): void
    {
        $request = new UpdateNameServersRequest([]);
        self::assertSame(['ns' => []], $request->toArray());
    }

    public function testArrayKeysAreReindexed(): void
    {
        $request = new UpdateNameServersRequest([5 => 'ns1.example.com', 9 => 'ns2.example.com']);
        self::assertSame(['ns1.example.com', 'ns2.example.com'], $request->ns);
    }

    public function testTooManyNameServersThrows(): void
    {
        $tooMany = array_fill(0, UpdateNameServersRequest::MAX_NAMESERVERS + 1, 'ns.example.com');
        $this->expectException(InvalidArgumentException::class);
        new UpdateNameServersRequest($tooMany);
    }
}
