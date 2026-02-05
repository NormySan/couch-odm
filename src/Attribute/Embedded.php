<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Attribute;

use Attribute;

/**
 * Maps a property to an embedded object within the document.
 *
 * @template T of object
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Embedded
{
    /**
     * @param class-string<T> $targetClass
     */
    public function __construct(
        public readonly string $targetClass,
        public readonly ?string $name = null,
    ) {}
}
