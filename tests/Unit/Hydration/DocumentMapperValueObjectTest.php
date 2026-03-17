<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Unit\Hydration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\Hydration\DocumentMapper;
use SmrtSystems\Couch\Hydration\Hydrator;
use SmrtSystems\Couch\Mapping\MetadataFactory;
use SmrtSystems\Couch\Tests\Fixtures\Converter\EmailConverter;
use SmrtSystems\Couch\Tests\Fixtures\Converter\MoneyConverter;
use SmrtSystems\Couch\Tests\Fixtures\UserWithValueObjects;
use SmrtSystems\Couch\Tests\Fixtures\ValueObject\Email;
use SmrtSystems\Couch\Tests\Fixtures\ValueObject\Money;
use SmrtSystems\Couch\Type\TypeConverterRegistry;

final class DocumentMapperValueObjectTest extends TestCase
{
    private DocumentMapper $mapper;
    private TypeConverterRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new TypeConverterRegistry([
            new EmailConverter(),
            new MoneyConverter(),
        ]);

        $metadataFactory = new MetadataFactory(
            cache: null,
            typeConverterRegistry: $this->registry,
        );

        $this->mapper = new DocumentMapper(
            $metadataFactory,
            new Hydrator(),
            $this->registry,
        );
    }

    #[Test]
    public function it_hydrates_value_objects(): void
    {
        $data = [
            '_id' => 'user-123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'balance' => ['amount' => 10000, 'currency' => 'USD'],
        ];

        $user = $this->mapper->toDocument(UserWithValueObjects::class, $data);

        $this->assertInstanceOf(UserWithValueObjects::class, $user);
        $this->assertSame('user-123', $user->id);
        $this->assertSame('John Doe', $user->name);

        $this->assertInstanceOf(Email::class, $user->email);
        $this->assertSame('john@example.com', $user->email->value);

        $this->assertInstanceOf(Money::class, $user->balance);
        $this->assertSame(10000, $user->balance->amount);
        $this->assertSame('USD', $user->balance->currency);
    }

    #[Test]
    public function it_extracts_value_objects(): void
    {
        $user = new UserWithValueObjects();
        $user->id = 'user-123';
        $user->name = 'John Doe';
        $user->email = new Email('john@example.com');
        $user->balance = new Money(10000, 'USD');

        $data = $this->mapper->toArray($user);

        $this->assertSame('user-123', $data['_id']);
        $this->assertSame('John Doe', $data['name']);
        $this->assertSame('john@example.com', $data['email']);
        $this->assertSame(['amount' => 10000, 'currency' => 'USD'], $data['balance']);
    }

    #[Test]
    public function it_handles_explicit_converter(): void
    {
        $data = [
            '_id' => 'user-123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'balance' => ['amount' => 0, 'currency' => 'USD'],
            'backupEmail' => 'backup@example.com',
        ];

        $user = $this->mapper->toDocument(UserWithValueObjects::class, $data);

        $this->assertInstanceOf(Email::class, $user->backupEmail);
        $this->assertSame('backup@example.com', $user->backupEmail->value);
    }

    #[Test]
    public function it_handles_null_value_objects(): void
    {
        $data = [
            '_id' => 'user-123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'balance' => ['amount' => 0, 'currency' => 'USD'],
            'backupEmail' => null,
        ];

        $user = $this->mapper->toDocument(UserWithValueObjects::class, $data);

        $this->assertNull($user->backupEmail);
    }

    #[Test]
    public function it_extracts_null_value_objects(): void
    {
        $user = new UserWithValueObjects();
        $user->id = 'user-123';
        $user->name = 'John Doe';
        $user->email = new Email('john@example.com');
        $user->balance = new Money(0, 'USD');
        $user->backupEmail = null;

        $data = $this->mapper->toArray($user);

        // Null nullable fields are not included in the output
        $this->assertArrayNotHasKey('backupEmail', $data);
    }

    #[Test]
    public function it_roundtrips_value_objects(): void
    {
        $originalUser = new UserWithValueObjects();
        $originalUser->id = 'user-456';
        $originalUser->name = 'Jane Doe';
        $originalUser->email = new Email('jane@example.com');
        $originalUser->balance = new Money(25000, 'EUR');
        $originalUser->backupEmail = new Email('jane.backup@example.com');

        // Extract to array
        $data = $this->mapper->toArray($originalUser);

        // Hydrate back to object
        $hydratedUser = $this->mapper->toDocument(UserWithValueObjects::class, $data);

        $this->assertSame($originalUser->id, $hydratedUser->id);
        $this->assertSame($originalUser->name, $hydratedUser->name);
        $this->assertSame($originalUser->email->value, $hydratedUser->email->value);
        $this->assertTrue($originalUser->balance->equals($hydratedUser->balance));
        $this->assertSame($originalUser->backupEmail->value, $hydratedUser->backupEmail->value);
    }
}
