<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests\Dto;

use Luchaninov\NicnamesClient\Dto\OrderDomainModel;
use PHPUnit\Framework\TestCase;

class OrderDomainModelTest extends TestCase
{
    public function testFullDecode(): void
    {
        $order = OrderDomainModel::createFromArray([
            'oid' => 'o54321',
            'type' => 'domain',
            'status' => ['active', 'lockTransfer'],
            'cts' => 1577836800,
            'uts' => 1577836900,
            'ets' => 1609459200,
            'domain' => [
                'name' => 'example.com',
                'registrant' => 'c1',
                'admin' => 'c2',
                'tech' => 'c3',
                'billing' => 'c4',
                'ns' => ['ns1.example.com', 'ns2.example.com'],
                'w3a' => 'eip155:1:0xcf9d756965e97dc144E90e447C5184B26861a8C0',
            ],
        ]);

        self::assertSame('o54321', $order->oid);
        self::assertSame('domain', $order->type);
        self::assertSame(['active', 'lockTransfer'], $order->status);
        self::assertSame(1577836800, $order->cts);
        self::assertSame(1609459200, $order->ets);
        self::assertNotNull($order->domain);
        self::assertSame('example.com', $order->domain->name);
        self::assertSame('c1', $order->domain->registrant);
        self::assertSame(['ns1.example.com', 'ns2.example.com'], $order->domain->ns);
        self::assertSame('eip155:1:0xcf9d756965e97dc144E90e447C5184B26861a8C0', $order->domain->w3a);
    }

    public function testMinimalDecodeWithoutDomain(): void
    {
        $order = OrderDomainModel::createFromArray([
            'oid' => 'o1',
            'type' => 'domain',
            'status' => [],
            'cts' => 1,
            'uts' => 1,
        ]);

        self::assertSame('o1', $order->oid);
        self::assertSame([], $order->status);
        self::assertNull($order->ets);
        self::assertNull($order->domain);
    }
}
