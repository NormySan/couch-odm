<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures\Converter;

use SmrtSystems\Couch\Tests\Fixtures\ValueObject\Money;
use SmrtSystems\Couch\Type\TypeConverterInterface;

/**
 * Converts Money value object to/from array.
 *
 * @implements TypeConverterInterface<Money>
 */
final class MoneyConverter implements TypeConverterInterface
{
    public function getPhpType(): string
    {
        return Money::class;
    }

    /**
     * @return array{amount: int, currency: string}|null
     */
    public function toDatabaseValue(mixed $value): ?array
    {
        if (!$value instanceof Money) {
            return null;
        }

        return [
            'amount' => $value->amount,
            'currency' => $value->currency,
        ];
    }

    public function toPhpValue(mixed $value): ?Money
    {
        if (!is_array($value)) {
            return null;
        }

        if (!isset($value['amount'], $value['currency'])) {
            return null;
        }

        return new Money((int) $value['amount'], (string) $value['currency']);
    }
}
