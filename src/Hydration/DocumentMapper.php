<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Hydration;

use DateTimeImmutable;
use DateTimeInterface;
use SmrtSystems\Couch\Mapping\ClassMetadata;
use SmrtSystems\Couch\Mapping\MetadataFactoryInterface;
use SmrtSystems\Couch\Mapping\PropertyMetadata;
use SmrtSystems\Couch\Mapping\PropertyType;

/**
 * Maps data between CouchDB documents and PHP objects.
 */
final class DocumentMapper implements DocumentMapperInterface
{
    public function __construct(
        private readonly MetadataFactoryInterface $metadataFactory,
        private readonly Hydrator $hydrator,
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

    public function getRevision(object $document): ?string
    {
        $metadata = $this->metadataFactory->getMetadataFor($document::class);

        if ($metadata->revisionProperty === null) {
            return null;
        }

        $value = $this->hydrator->getValue($document, $metadata->revisionProperty->propertyName);

        return $value !== null ? (string) $value : null;
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
            PropertyType::Revision,
            PropertyType::String => (string) $value,
            PropertyType::Int => (int) $value,
            PropertyType::Float => (float) $value,
            PropertyType::Bool => (bool) $value,
            PropertyType::Array => (array) $value,
            PropertyType::DateTime => $this->convertToDateTime($value),
            PropertyType::Embedded => $this->hydrateEmbedded($property, $value),
            PropertyType::EmbeddedCollection => $this->hydrateEmbeddedCollection($property, $value),
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
}
