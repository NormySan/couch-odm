<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Attribute;

use Attribute;

/**
 * Marks a property as the document revision (_rev field).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Revision {}
