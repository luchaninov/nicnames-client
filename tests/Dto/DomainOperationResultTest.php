<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests\Dto;

use Luchaninov\NicnamesClient\Dto\DomainOperationResult;
use Luchaninov\NicnamesClient\Dto\OrderDomainModel;
use PHPUnit\Framework\TestCase;

class DomainOperationResultTest extends TestCase
{
    public function testFromOrder(): void
    {
        $order = OrderDomainModel::createFromArray([
            'oid' => 'o1',
            'type' => 'domain',
            'status' => ['active'],
            'cts' => 1,
            'uts' => 1,
        ]);
        $result = DomainOperationResult::fromOrder($order);

        self::assertFalse($result->isAsync());
        self::assertSame($order, $result->order);
        self::assertNull($result->jobId);
    }

    public function testFromJob(): void
    {
        $result = DomainOperationResult::fromJob('JOB1');

        self::assertTrue($result->isAsync());
        self::assertSame('JOB1', $result->jobId);
        self::assertNull($result->order);
    }
}
