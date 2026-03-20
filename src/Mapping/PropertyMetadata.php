<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Mapping;

/**
 * Holds metadata about a single mapped property.
 */
final class PropertyMetadata
{
    /**
     * @param class-string|null $targetClass Target class for Embedded/EmbeddedCollection types
     * @param class-string|null $converterClass Converter class for ValueObject types
     */
    public function __construct(
        public readonly string $propertyName,
        public readonly string $fieldName,
        public readonly PropertyType $type,
        public readonly bool $nullable,
        public readonly mixed $default,
        public readonly ?string $targetClass = null,
        public readonly ?string $converterClass = null,
    ) {}

    public function isId(): bool
    {
        return $this->type === PropertyType::Id;
    }

    public function isEmbedded(): bool
    {
        return $this->type === PropertyType::Embedded;
    }

    public function isEmbeddedCollection(): bool
    {
        return $this->type === PropertyType::EmbeddedCollection;
    }

    public function isValueObject(): bool
    {
        return $this->type === PropertyType::ValueObject;
    }
}
