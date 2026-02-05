<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Attribute;

use Attribute;

/**
 * Maps a property to a CouchDB document field.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Field
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly bool $nullable = true,
        public readonly mixed $default = null,
    ) {}
}
