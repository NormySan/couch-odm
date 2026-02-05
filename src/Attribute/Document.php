<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Attribute;

use Attribute;

/**
 * Marks a class as a CouchDB document.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Document
{
    public function __construct(
        public readonly string $database,
        public readonly ?string $type = null,
    ) {}
}
