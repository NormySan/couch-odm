<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Attribute;

use Attribute;

/**
 * Marks a class as an embedded document (not stored independently).
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EmbeddedDocument
{
}
