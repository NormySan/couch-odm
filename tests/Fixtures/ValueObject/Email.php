<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures\ValueObject;

/**
 * Simple value object representing an email address.
 */
final class Email
{
    public function __construct(
        public readonly string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email: {$value}");
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
