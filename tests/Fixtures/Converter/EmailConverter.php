<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures\Converter;

use SmrtSystems\Couch\Tests\Fixtures\ValueObject\Email;
use SmrtSystems\Couch\Type\TypeConverterInterface;

/**
 * Converts Email value object to/from string.
 *
 * @implements TypeConverterInterface<Email>
 */
final class EmailConverter implements TypeConverterInterface
{
    public function getPhpType(): string
    {
        return Email::class;
    }

    public function toDatabaseValue(mixed $value): ?string
    {
        if (!$value instanceof Email) {
            return null;
        }

        return $value->value;
    }

    public function toPhpValue(mixed $value): ?Email
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            return new Email($value);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
