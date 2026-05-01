<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests\Dto;

use Luchaninov\NicnamesClient\Dto\OperationModel;
use Luchaninov\NicnamesClient\Dto\PeriodModel;
use Luchaninov\NicnamesClient\Dto\PeriodUnitModel;
use Luchaninov\NicnamesClient\Dto\PriceModel;
use PHPUnit\Framework\TestCase;

class PriceModelTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $price = new PriceModel(12.34, 840, OperationModel::CREATE, new PeriodModel(PeriodUnitModel::YEARS, 2));
        $array = $price->toArray();
        $decoded = PriceModel::createFromArray($array);

        self::assertSame(12.34, $decoded->amt);
        self::assertSame(840, $decoded->ccy);
        self::assertSame(OperationModel::CREATE, $decoded->op);
        self::assertSame(PeriodUnitModel::YEARS, $decoded->period->unit);
        self::assertSame(2, $decoded->period->value);
    }

    public function testCreateFromArrayDefaults(): void
    {
        $price = PriceModel::createFromArray([]);
        self::assertSame(0.0, $price->amt);
        self::assertSame(0, $price->ccy);
        self::assertSame(OperationModel::NONE, $price->op);
        self::assertSame(PeriodUnitModel::YEARS, $price->period->unit);
        self::assertSame(1, $price->period->value);
    }

    public function testCreateFromArrayUnknownEnumValuesFallBackToDefaults(): void
    {
        $price = PriceModel::createFromArray(['op' => 'NOT_A_THING', 'period' => ['unit' => 'WEEKS', 'value' => 4]]);
        self::assertSame(OperationModel::NONE, $price->op);
        self::assertSame(PeriodUnitModel::YEARS, $price->period->unit);
        self::assertSame(4, $price->period->value);
    }

    public function testPeriodModelRoundTrip(): void
    {
        $period = new PeriodModel(PeriodUnitModel::MONTHS, 6);
        self::assertSame(['unit' => 'MONTHS', 'value' => 6], $period->toArray());
        $decoded = PeriodModel::createFromArray($period->toArray());
        self::assertSame(PeriodUnitModel::MONTHS, $decoded->unit);
        self::assertSame(6, $decoded->value);
    }

    public function testToArrayUsesEnumValues(): void
    {
        $price = new PriceModel(1.0, 840, OperationModel::TRANSFER, new PeriodModel(PeriodUnitModel::MONTHS, 12));
        self::assertSame(
            [
                'amt' => 1.0,
                'ccy' => 840,
                'op' => 'TRANSFER',
                'period' => ['unit' => 'MONTHS', 'value' => 12],
            ],
            $price->toArray(),
        );
    }
}
