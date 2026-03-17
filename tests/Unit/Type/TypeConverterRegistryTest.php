<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Unit\Type;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\Tests\Fixtures\Converter\EmailConverter;
use SmrtSystems\Couch\Tests\Fixtures\Converter\MoneyConverter;
use SmrtSystems\Couch\Tests\Fixtures\ValueObject\Email;
use SmrtSystems\Couch\Tests\Fixtures\ValueObject\Money;
use SmrtSystems\Couch\Type\TypeConverterRegistry;

final class TypeConverterRegistryTest extends TestCase
{
    #[Test]
    public function it_creates_empty_registry(): void
    {
        $registry = new TypeConverterRegistry();

        $this->assertSame([], $registry->all());
        $this->assertSame([], $registry->getRegisteredTypes());
    }

    #[Test]
    public function it_registers_converter(): void
    {
        $registry = new TypeConverterRegistry();
        $converter = new EmailConverter();

        $registry->register($converter);

        $this->assertTrue($registry->has(Email::class));
        $this->assertSame($converter, $registry->get(Email::class));
    }

    #[Test]
    public function it_registers_multiple_converters(): void
    {
        $emailConverter = new EmailConverter();
        $moneyConverter = new MoneyConverter();

        $registry = new TypeConverterRegistry([
            $emailConverter,
            $moneyConverter,
        ]);

        $this->assertTrue($registry->has(Email::class));
        $this->assertTrue($registry->has(Money::class));
        $this->assertSame($emailConverter, $registry->get(Email::class));
        $this->assertSame($moneyConverter, $registry->get(Money::class));
    }

    #[Test]
    public function it_returns_null_for_unregistered_type(): void
    {
        $registry = new TypeConverterRegistry();

        $this->assertFalse($registry->has(Email::class));
        $this->assertNull($registry->get(Email::class));
    }

    #[Test]
    public function it_returns_all_registered_types(): void
    {
        $registry = new TypeConverterRegistry([
            new EmailConverter(),
            new MoneyConverter(),
        ]);

        $types = $registry->getRegisteredTypes();

        $this->assertContains(Email::class, $types);
        $this->assertContains(Money::class, $types);
    }

    #[Test]
    public function it_overwrites_converter_for_same_type(): void
    {
        $registry = new TypeConverterRegistry();
        $converter1 = new EmailConverter();
        $converter2 = new EmailConverter();

        $registry->register($converter1);
        $registry->register($converter2);

        $this->assertSame($converter2, $registry->get(Email::class));
    }
}
