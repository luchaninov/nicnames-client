<?php

declare(strict_types=1);

namespace Luchaninov\NicnamesClient\Tests\Dto;

use Luchaninov\NicnamesClient\Dto\ContactModel;
use PHPUnit\Framework\TestCase;

class ContactModelTest extends TestCase
{
    public function testFullDecode(): void
    {
        $c = ContactModel::createFromArray([
            'contactId' => 'c987654321',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'org' => 'Acme Corporation',
            'orgPhone' => '+15551234567',
            'cc' => 'us',
            'pc' => '62704',
            'sp' => 'IL',
            'city' => 'Springfield',
            'addr' => '123 Main Street',
            'email' => 'john.doe@example.com',
            'phone' => '+15551234567',
            'phonePolicy' => true,
        ]);

        self::assertSame('c987654321', $c->contactId);
        self::assertSame('John', $c->firstName);
        self::assertSame('Acme Corporation', $c->org);
        self::assertSame('+15551234567', $c->orgPhone);
        self::assertTrue($c->phonePolicy);
        self::assertNull($c->middleName);
        self::assertNull($c->fax);
    }
}
