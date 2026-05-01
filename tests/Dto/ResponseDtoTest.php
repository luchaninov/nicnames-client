<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests\Dto;

use Luchaninov\NicnamesClient\Dto\ContactList;
use Luchaninov\NicnamesClient\Dto\DomainCheckResult;
use Luchaninov\NicnamesClient\Dto\DomainList;
use Luchaninov\NicnamesClient\Dto\DomainModel;
use Luchaninov\NicnamesClient\Dto\EmailVerificationStatus;
use Luchaninov\NicnamesClient\Dto\OperationModel;
use Luchaninov\NicnamesClient\Dto\OrderModel;
use Luchaninov\NicnamesClient\Dto\TierModel;
use PHPUnit\Framework\TestCase;

class ResponseDtoTest extends TestCase
{
    public function testOrderModelBaseDecode(): void
    {
        $order = OrderModel::createFromArray([
            'oid' => 'o-base',
            'type' => 'common',
            'status' => ['active'],
            'cts' => 1577836800,
            'uts' => 1577836900,
            'ets' => 1609459200,
        ]);
        self::assertSame('o-base', $order->oid);
        self::assertSame('common', $order->type);
        self::assertSame(['active'], $order->status);
        self::assertSame(1577836800, $order->cts);
        self::assertSame(1577836900, $order->uts);
        self::assertSame(1609459200, $order->ets);
    }

    public function testOrderModelDefaults(): void
    {
        $order = OrderModel::createFromArray([]);
        self::assertSame('', $order->oid);
        self::assertSame('common', $order->type);
        self::assertSame([], $order->status);
        self::assertNull($order->ets);
    }

    public function testOrderModelDiscriminatorDispatchesToDomain(): void
    {
        $order = OrderModel::createFromArray([
            'oid' => 'o1',
            'type' => 'domain',
            'status' => ['active'],
            'cts' => 1,
            'uts' => 1,
            'domain' => ['name' => 'example.com', 'registrant' => 'c1'],
        ]);
        self::assertInstanceOf(\Luchaninov\NicnamesClient\Dto\OrderDomainModel::class, $order);
    }

    public function testDomainModelDecode(): void
    {
        $domain = DomainModel::createFromArray([
            'name' => 'example.com',
            'registrant' => 'c1',
            'admin' => 'c2',
            'tech' => 'c3',
            'billing' => 'c4',
            'ns' => ['ns1', 'ns2', 'ns3'],
            'w3a' => 'eip155:1:0xabc',
        ]);
        self::assertSame('example.com', $domain->name);
        self::assertSame('c1', $domain->registrant);
        self::assertSame(['ns1', 'ns2', 'ns3'], $domain->ns);
        self::assertSame('eip155:1:0xabc', $domain->w3a);
    }

    public function testDomainModelDefaults(): void
    {
        $domain = DomainModel::createFromArray([]);
        self::assertSame('', $domain->name);
        self::assertNull($domain->admin);
        self::assertNull($domain->w3a);
        self::assertSame([], $domain->ns);
    }

    public function testDomainListDecode(): void
    {
        $list = DomainList::createFromArray([
            'total' => 3,
            'list' => [
                ['oid' => 'o1', 'type' => 'domain', 'status' => ['active'], 'cts' => 1, 'uts' => 1],
                ['oid' => 'o2', 'type' => 'domain', 'status' => ['active'], 'cts' => 2, 'uts' => 2],
            ],
        ]);
        self::assertSame(3, $list->total);
        self::assertCount(2, $list->list);
        self::assertSame('o1', $list->list[0]->oid);
    }

    public function testDomainListEmpty(): void
    {
        $list = DomainList::createFromArray([]);
        self::assertSame(0, $list->total);
        self::assertSame([], $list->list);
    }

    public function testContactListDecode(): void
    {
        $list = ContactList::createFromArray([
            'total' => 1,
            'list' => [[
                'contactId' => 'c1',
                'firstName' => 'A',
                'lastName' => 'B',
                'cc' => 'us',
                'pc' => '1',
                'sp' => 'X',
                'city' => 'Y',
                'addr' => 'Z',
                'email' => 'a@b.c',
                'phone' => '+1',
                'phonePolicy' => false,
            ]],
        ]);
        self::assertSame(1, $list->total);
        self::assertSame('c1', $list->list[0]->contactId);
    }

    public function testDomainCheckResultDecode(): void
    {
        $check = DomainCheckResult::createFromArray([
            'domainName' => 'example.com',
            'availableFor' => 'CREATE',
            'tier' => 'PREMIUM',
            'price' => [
                ['amt' => 99.0, 'ccy' => 840, 'op' => 'CREATE', 'period' => ['unit' => 'YEARS', 'value' => 1]],
            ],
        ]);
        self::assertSame('example.com', $check->domainName);
        self::assertSame(OperationModel::CREATE, $check->availableFor);
        self::assertSame(TierModel::PREMIUM, $check->tier);
        self::assertCount(1, $check->price);
        self::assertSame(99.0, $check->price[0]->amt);
    }

    public function testDomainCheckResultDefaults(): void
    {
        $check = DomainCheckResult::createFromArray([]);
        self::assertSame('', $check->domainName);
        self::assertSame(OperationModel::NONE, $check->availableFor);
        self::assertSame(TierModel::UNKNOWN, $check->tier);
        self::assertSame([], $check->price);
    }

    public function testDomainCheckResultUnknownEnumValuesFallBack(): void
    {
        $check = DomainCheckResult::createFromArray([
            'availableFor' => 'EXOTIC_OP',
            'tier' => 'UNDOCUMENTED',
        ]);
        self::assertSame(OperationModel::NONE, $check->availableFor);
        self::assertSame(TierModel::UNKNOWN, $check->tier);
    }

    public function testEmailVerificationStatusFull(): void
    {
        $status = EmailVerificationStatus::createFromArray(['code' => 441000, 'email' => 'a@b.c']);
        self::assertSame(441000, $status->code);
        self::assertSame('a@b.c', $status->email);
    }

    public function testEmailVerificationStatusWithoutEmail(): void
    {
        $status = EmailVerificationStatus::createFromArray(['code' => 441001]);
        self::assertSame(441001, $status->code);
        self::assertNull($status->email);
    }
}
