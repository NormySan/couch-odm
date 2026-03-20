<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Hydration;

use DateTimeImmutable;
use DateTimeInterface;
use SmrtSystems\Couch\Exception\MappingException;
use SmrtSystems\Couch\Mapping\ClassMetadata;
use SmrtSystems\Couch\Mapping\MetadataFactoryInterface;
use SmrtSystems\Couch\Mapping\PropertyMetadata;
use SmrtSystems\Couch\Mapping\PropertyType;
use SmrtSystems\Couch\Type\TypeConverterInterface;
use SmrtSystems\Couch\Type\TypeConverterRegistry;

/**
 * Maps data between CouchDB documents and PHP objects.
 */
final class DocumentMapper implements DocumentMapperInterface
{
    /** @var array<class-string, TypeConverterInterface<object>> */
    private array $converterInstances = [];

    public function __construct(
        private readonly MetadataFactoryInterface $metadataFactory,
        private readonly Hydrator $hydrator,
        private readonly ?TypeConverterRegistry $typeConverterRegistry = null,
    ) {}

    public function toDocument(string $className, array $data): object
    {
        $metadata = $this->metadataFactory->getMetadataFor($className);

        return $this->hydrateObject($metadata, $data);
    }

    public function toArray(object $document): array
    {
        $metadata = $this->metadataFactory->getMetadataFor($document::class);

        return $this->extractArray($metadata, $document);
    }

    public function getDatabase(string $className): string
    {
        return $this->metadataFactory->getMetadataFor($className)->database;
    }

    public function getId(object $document): ?string
    {
        $metadata = $this->metadataFactory->getMetadataFor($document::class);

        if ($metadata->idProperty === null) {
            return null;
        }

        $value = $this->hydrator->getValue($document, $metadata->idProperty->propertyName);

        return $value !== null ? (string) $value : null;
    }

    public function setId(object $document, string $id): void {
        $metadata = $this->metadataFactory->getMetadataFor($document::class);

        if ($metadata->idProperty === null) {
            throw new MappingException('Document does not have an ID property');
        }

        $this->hydrator->setValue(
            document: $document,
            propertyName: $metadata->idProperty->propertyName,
            value: $id,
        );
    }

    private function hydrateObject(ClassMetadata $metadata, array $data): object
    {
        $values = [];

        foreach ($metadata->properties as $property) {
            $fieldValue = $data[$property->fieldName] ?? $property->default;
            $values[$property->propertyName] = $this->convertToPhp($property, $fieldValue);
        }

        return $this->hydrator->hydrate($metadata->className, $values);
    }

    private function extractArray(ClassMetadata $metadata, object $document): array
    {
        $data = [];
        $values = $this->hydrator->extract($document);

        foreach ($metadata->properties as $property) {
            $value = $values[$property->propertyName] ?? null;
            $converted = $this->convertToDatabase($property, $value);

            // Only include non-null values or explicitly nullable fields
            if ($converted !== null || !$property->nullable) {
                $data[$property->fieldName] = $converted;
            }
        }

        return $data;
    }

    private function convertToPhp(PropertyMetadata $property, mixed $value): mixed
    {
        if ($value === null) {
            return $property->nullable ? null : $property->default;
        }

        return match ($property->type) {
            PropertyType::Id,
            PropertyType::String => (string) $value,
            PropertyType::Int => (int) $value,
            PropertyType::Float => (float) $value,
            PropertyType::Bool => (bool) $value,
            PropertyType::Array => (array) $value,
            PropertyType::DateTime => $this->convertToDateTime($value),
            PropertyType::Embedded => $this->hydrateEmbedded($property, $value),
            PropertyType::EmbeddedCollection => $this->hydrateEmbeddedCollection($property, $value),
            PropertyType::ValueObject => $this->convertValueObjectToPhp($property, $value),
            PropertyType::Mixed => $value,
        };
    }

    private function convertToDatabase(PropertyMetadata $property, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($property->type) {
            PropertyType::DateTime => $this->convertFromDateTime($value),
            PropertyType::Embedded => $this->extractEmbedded($property, $value),
            PropertyType::EmbeddedCollection => $this->extractEmbeddedCollection($property, $value),
            PropertyType::ValueObject => $this->convertValueObjectToDatabase($property, $value),
            default => $value,
        };
    }

    private function convertToDateTime(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (is_string($value)) {
            $dateTime = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $value);
            if ($dateTime !== false) {
                return $dateTime;
            }

            // Try parsing as any valid date string
            try {
                return new DateTimeImmutable($value);
            } catch (\Exception) {
                return null;
            }
        }

        if (is_int($value)) {
            return (new DateTimeImmutable())->setTimestamp($value);
        }

        return null;
    }

    private function convertFromDateTime(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        return null;
    }

    private function hydrateEmbedded(PropertyMetadata $property, mixed $data): ?object
    {
        if (!is_array($data) || $property->targetClass === null) {
            return null;
        }

        $metadata = $this->metadataFactory->getMetadataFor($property->targetClass);

        return $this->hydrateObject($metadata, $data);
    }

    /**
     * @return array<int, object>
     */
    private function hydrateEmbeddedCollection(PropertyMetadata $property, mixed $data): array
    {
        if (!is_array($data) || $property->targetClass === null) {
            return [];
        }

        $metadata = $this->metadataFactory->getMetadataFor($property->targetClass);

        return array_map(
            fn(mixed $item): object => is_array($item)
                ? $this->hydrateObject($metadata, $item)
                : $this->hydrator->hydrate($metadata->className, []),
            array_values($data)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function extractEmbedded(PropertyMetadata $property, object $value): array
    {
        if ($property->targetClass === null) {
            return [];
        }

        $metadata = $this->metadataFactory->getMetadataFor($property->targetClass);

        return $this->extractArray($metadata, $value);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractEmbeddedCollection(PropertyMetadata $property, mixed $values): array
    {
        if (!is_iterable($values) || $property->targetClass === null) {
            return [];
        }

        $metadata = $this->metadataFactory->getMetadataFor($property->targetClass);
        $result = [];

        foreach ($values as $value) {
            if (is_object($value)) {
                $result[] = $this->extractArray($metadata, $value);
            }
        }

        return $result;
    }

    /**
     * Convert a database value to a PHP value object using a type converter.
     */
    private function convertValueObjectToPhp(PropertyMetadata $property, mixed $value): mixed
    {
        $converter = $this->getConverter($property);
        if ($converter === null) {
            return $value;
        }

        return $converter->toPhpValue($value);
    }

    /**
     * Convert a PHP value object to its database representation using a type converter.
     */
    private function convertValueObjectToDatabase(PropertyMetadata $property, mixed $value): mixed
    {
        $converter = $this->getConverter($property);
        if ($converter === null) {
            return $value;
        }

        return $converter->toDatabaseValue($value);
    }

    /**
     * Get the type converter for a property.
     *
     * @return TypeConverterInterface<object>|null
     */
    private function getConverter(PropertyMetadata $property): ?TypeConverterInterface
    {
        // First check for explicit converter class
        if ($property->converterClass !== null) {
            return $this->getConverterInstance($property->converterClass);
        }

        // Then check registry by target class
        if ($property->targetClass !== null && $this->typeConverterRegistry !== null) {
            return $this->typeConverterRegistry->get($property->targetClass);
        }

        return null;
    }

    /**
     * Get or create a converter instance from class name.
     *
     * @param class-string $converterClass
     * @return TypeConverterInterface<object>|null
     */
    private function getConverterInstance(string $converterClass): ?TypeConverterInterface
    {
        if (!isset($this->converterInstances[$converterClass])) {
            if (!class_exists($converterClass)) {
                return null;
            }

            $instance = new $converterClass();
            if (!$instance instanceof TypeConverterInterface) {
                return null;
            }

            $this->converterInstances[$converterClass] = $instance;
        }

        return $this->converterInstances[$converterClass];
    }
}
