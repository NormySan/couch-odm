<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Mapping;

/**
 * Holds metadata about a mapped document class.
 */
final class ClassMetadata
{
    /**
     * @param class-string $className
     * @param array<string, PropertyMetadata> $properties Keyed by property name
     */
    public function __construct(
        public readonly string $className,
        public readonly ?string $database,
        public readonly ?string $type,
        public readonly ?PropertyMetadata $idProperty,
        public readonly array $properties,
        public readonly int $loadedAt = 0,
    ) {}

    public function getProperty(string $name): ?PropertyMetadata
    {
        return $this->properties[$name] ?? null;
    }

    public function getPropertyByFieldName(string $fieldName): ?PropertyMetadata
    {
        foreach ($this->properties as $property) {
            if ($property->fieldName === $fieldName) {
                return $property;
            }
        }

        return null;
    }

    /**
     * Returns a mapping of CouchDB field names to property names.
     *
     * @return array<string, string>
     */
    public function getFieldMapping(): array
    {
        $mapping = [];
        foreach ($this->properties as $property) {
            $mapping[$property->fieldName] = $property->propertyName;
        }

        return $mapping;
    }

    /**
     * Returns a mapping of property names to CouchDB field names.
     *
     * @return array<string, string>
     */
    public function getPropertyMapping(): array
    {
        $mapping = [];
        foreach ($this->properties as $property) {
            $mapping[$property->propertyName] = $property->fieldName;
        }

        return $mapping;
    }

    /**
     * @return array<string, PropertyMetadata>
     */
    public function getEmbeddedProperties(): array
    {
        return array_filter(
            $this->properties,
            static fn(PropertyMetadata $p): bool => $p->isEmbedded() || $p->isEmbeddedCollection()
        );
    }
}
