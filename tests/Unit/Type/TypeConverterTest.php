<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Unit\Type;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\Tests\Fixtures\Converter\EmailConverter;
use SmrtSystems\Couch\Tests\Fixtures\Converter\MoneyConverter;
use SmrtSystems\Couch\Tests\Fixtures\ValueObject\Email;
use SmrtSystems\Couch\Tests\Fixtures\ValueObject\Money;

final class TypeConverterTest extends TestCase
{
    #[Test]
    public function email_converter_converts_to_database_value(): void
    {
        $converter = new EmailConverter();
        $email = new Email('test@example.com');

        $result = $converter->toDatabaseValue($email);

        $this->assertSame('test@example.com', $result);
    }

    #[Test]
    public function email_converter_converts_to_php_value(): void
    {
        $converter = new EmailConverter();

        $result = $converter->toPhpValue('test@example.com');

        $this->assertInstanceOf(Email::class, $result);
        $this->assertSame('test@example.com', $result->value);
    }

    #[Test]
    public function email_converter_returns_null_for_invalid_input(): void
    {
        $converter = new EmailConverter();

        $this->assertNull($converter->toDatabaseValue('not-an-email-object'));
        $this->assertNull($converter->toPhpValue('invalid-email'));
        $this->assertNull($converter->toPhpValue(''));
        $this->assertNull($converter->toPhpValue(null));
    }

    #[Test]
    public function money_converter_converts_to_database_value(): void
    {
        $converter = new MoneyConverter();
        $money = new Money(10000, 'USD');

        $result = $converter->toDatabaseValue($money);

        $this->assertSame(['amount' => 10000, 'currency' => 'USD'], $result);
    }

    #[Test]
    public function money_converter_converts_to_php_value(): void
    {
        $converter = new MoneyConverter();

        $result = $converter->toPhpValue(['amount' => 5000, 'currency' => 'EUR']);

        $this->assertInstanceOf(Money::class, $result);
        $this->assertSame(5000, $result->amount);
        $this->assertSame('EUR', $result->currency);
    }

    #[Test]
    public function money_converter_returns_null_for_invalid_input(): void
    {
        $converter = new MoneyConverter();

        $this->assertNull($converter->toDatabaseValue('not-a-money-object'));
        $this->assertNull($converter->toPhpValue('invalid'));
        $this->assertNull($converter->toPhpValue(['amount' => 100])); // Missing currency
        $this->assertNull($converter->toPhpValue(null));
    }

    #[Test]
    public function email_converter_returns_correct_php_type(): void
    {
        $converter = new EmailConverter();

        $this->assertSame(Email::class, $converter->getPhpType());
    }

    #[Test]
    public function money_converter_returns_correct_php_type(): void
    {
        $converter = new MoneyConverter();

        $this->assertSame(Money::class, $converter->getPhpType());
    }
}
