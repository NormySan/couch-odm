<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures\ValueObject;

/**
 * Value object representing a monetary amount.
 */
final class Money
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
    ) {}

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
}
