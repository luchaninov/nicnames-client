<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests\Dto;

use Luchaninov\NicnamesClient\Dto\CreateContactRequest;
use Luchaninov\NicnamesClient\Dto\CreateDomainRequest;
use Luchaninov\NicnamesClient\Dto\ListParams;
use Luchaninov\NicnamesClient\Dto\OperationModel;
use Luchaninov\NicnamesClient\Dto\PeriodModel;
use Luchaninov\NicnamesClient\Dto\PeriodUnitModel;
use Luchaninov\NicnamesClient\Dto\PriceModel;
use Luchaninov\NicnamesClient\Dto\RenewDomainRequest;
use Luchaninov\NicnamesClient\Dto\RestoreDomainRequest;
use Luchaninov\NicnamesClient\Dto\TransferDomainRequest;
use Luchaninov\NicnamesClient\Dto\UpdateWhoisPrivacyRequest;
use PHPUnit\Framework\TestCase;

class RequestDtoTest extends TestCase
{
    private function price(string $op = OperationModel::CREATE): PriceModel
    {
        return new PriceModel(12.34, 840, $op, new PeriodModel(PeriodUnitModel::YEARS, 1));
    }

    public function testCreateDomainRequestMinimal(): void
    {
        $request = new CreateDomainRequest($this->price(), 'c987654321');
        self::assertSame(
            [
                'price' => ['amt' => 12.34, 'ccy' => 840, 'op' => 'CREATE', 'period' => ['unit' => 'YEARS', 'value' => 1]],
                'registrant' => 'c987654321',
            ],
            $request->toArray(),
        );
    }

    public function testCreateDomainRequestFull(): void
    {
        $request = new CreateDomainRequest(
            $this->price(),
            registrant: 'c1',
            admin: 'c2',
            tech: 'c3',
            billing: 'c4',
            ns: ['ns1.example.com', 'ns2.example.com'],
        );
        $array = $request->toArray();

        self::assertSame('c1', $array['registrant']);
        self::assertSame('c2', $array['admin']);
        self::assertSame('c3', $array['tech']);
        self::assertSame('c4', $array['billing']);
        self::assertSame(['ns1.example.com', 'ns2.example.com'], $array['ns']);
    }

    public function testRenewDomainRequest(): void
    {
        $request = new RenewDomainRequest($this->price(OperationModel::RENEW), currentETS: 1577836800);
        $array = $request->toArray();

        self::assertSame(1577836800, $array['currentETS']);
        self::assertSame('RENEW', $array['price']['op']);
    }

    public function testRestoreDomainRequest(): void
    {
        $request = new RestoreDomainRequest($this->price(OperationModel::RESTORE));
        self::assertSame(['price' => $request->price->toArray()], $request->toArray());
    }

    public function testTransferDomainRequest(): void
    {
        $request = new TransferDomainRequest($this->price(OperationModel::TRANSFER), 'c1', 'AUTH');
        $array = $request->toArray();
        self::assertSame('c1', $array['registrant']);
        self::assertSame('AUTH', $array['authCode']);
    }

    public function testCreateContactRequestMinimal(): void
    {
        $request = new CreateContactRequest(
            firstName: 'John',
            lastName: 'Doe',
            cc: 'us',
            pc: '62704',
            sp: 'IL',
            city: 'Springfield',
            addr: '123',
            email: 'a@b.c',
            phone: '+1',
            phonePolicy: true,
        );
        $array = $request->toArray();
        self::assertArrayNotHasKey('middleName', $array);
        self::assertArrayNotHasKey('org', $array);
        self::assertArrayNotHasKey('orgPhone', $array);
        self::assertArrayNotHasKey('fax', $array);
        self::assertSame('John', $array['firstName']);
        self::assertTrue($array['phonePolicy']);
    }

    public function testCreateContactRequestFull(): void
    {
        $request = new CreateContactRequest(
            firstName: 'John',
            lastName: 'Doe',
            cc: 'us',
            pc: '62704',
            sp: 'IL',
            city: 'Springfield',
            addr: '123',
            email: 'a@b.c',
            phone: '+1',
            phonePolicy: true,
            middleName: 'M',
            org: 'Acme',
            orgPhone: '+2',
            fax: '+3',
        );
        $array = $request->toArray();
        self::assertSame('M', $array['middleName']);
        self::assertSame('Acme', $array['org']);
        self::assertSame('+2', $array['orgPhone']);
        self::assertSame('+3', $array['fax']);
    }

    public function testUpdateWhoisPrivacyRequestEmpty(): void
    {
        $request = new UpdateWhoisPrivacyRequest();
        self::assertSame([], $request->toArray());
    }

    public function testUpdateWhoisPrivacyRequestAllFields(): void
    {
        $request = new UpdateWhoisPrivacyRequest(
            registrant: UpdateWhoisPrivacyRequest::ENABLE,
            admin: UpdateWhoisPrivacyRequest::DISABLE,
            tech: UpdateWhoisPrivacyRequest::ENABLE,
            billing: UpdateWhoisPrivacyRequest::DISABLE,
        );
        self::assertSame(
            [
                'registrant' => 'enable',
                'admin' => 'disable',
                'tech' => 'enable',
                'billing' => 'disable',
            ],
            $request->toArray(),
        );
    }

    public function testListParamsDefaults(): void
    {
        $params = new ListParams();
        self::assertSame(['pgn' => 1, 'pgl' => 25], $params->toArray());
    }

    public function testListParamsWithFilter(): void
    {
        $params = new ListParams(pgn: 3, pgl: 50, filter: "email = 'x@y.z'");
        self::assertSame(
            ['pgn' => 3, 'pgl' => 50, 'filter' => "email = 'x@y.z'"],
            $params->toArray(),
        );
    }
}
