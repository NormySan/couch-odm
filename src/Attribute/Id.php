<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Attribute;

use Attribute;

/**
 * Marks a property as the document ID (_id field).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Id
{
    public const STRATEGY_UUID = 'uuid';
    public const STRATEGY_ASSIGNED = 'assigned';

    public function __construct(
        public readonly string $strategy = self::STRATEGY_UUID,
    ) {}
}
