<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Attribute;

use Attribute;
use SmrtSystems\Couch\Type\TypeConverterInterface;

/**
 * Maps a property to a CouchDB document field.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Field
{
    /**
     * @param string|null $name The field name in CouchDB (defaults to property name)
     * @param string|null $type The type hint: 'string', 'int', 'float', 'bool', 'array', 'datetime'
     * @param bool $nullable Whether the field allows null values
     * @param mixed $default Default value when field is missing
     * @param class-string<TypeConverterInterface<object>>|null $converter Explicit converter class for value objects
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly bool $nullable = true,
        public readonly mixed $default = null,
        public readonly ?string $converter = null,
    ) {}
}
