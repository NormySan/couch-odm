<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Mapping;

use DateTimeInterface;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Embedded;
use SmrtSystems\Couch\Attribute\EmbeddedCollection;
use SmrtSystems\Couch\Attribute\Field;
use SmrtSystems\Couch\Attribute\Id;
use SmrtSystems\Couch\Attribute\Revision;
use SmrtSystems\Couch\Exception\MappingException;
use SmrtSystems\Couch\Type\TypeConverterRegistry;

/**
 * Creates and caches class metadata from PHP attributes.
 */
final class MetadataFactory implements MetadataFactoryInterface
{
    /** @var array<class-string, ClassMetadata> */
    private array $loadedMetadata = [];

    public function __construct(
        private readonly ?CacheItemPoolInterface $cache = null,
        private readonly ?TypeConverterRegistry $typeConverterRegistry = null,
        private readonly bool $debug = false,
    ) {}

    public function getMetadataFor(string $className): ClassMetadata
    {
        if (isset($this->loadedMetadata[$className])) {
            return $this->loadedMetadata[$className];
        }

        if (!class_exists($className)) {
            throw MappingException::classNotFound($className);
        }

        $cacheKey = $this->getCacheKey($className);

        if ($this->cache !== null) {
            $item = $this->cache->getItem($cacheKey);
            if ($item->isHit()) {
                $cached = $item->get();
                if ($cached instanceof ClassMetadata && !$this->isOutdated($className, $cached)) {
                    return $this->loadedMetadata[$className] = $cached;
                }
            }
        }

        $metadata = $this->loadMetadata($className);

        if ($this->cache !== null) {
            $item = $this->cache->getItem($cacheKey);
            $item->set($metadata);
            $this->cache->save($item);
        }

        return $this->loadedMetadata[$className] = $metadata;
    }

    public function hasMetadataFor(string $className): bool
    {
        try {
            $this->getMetadataFor($className);

            return true;
        } catch (MappingException) {
            return false;
        }
    }

    /**
     * @param class-string $className
     */
    private function loadMetadata(string $className): ClassMetadata
    {
        $reflectionClass = new ReflectionClass($className);

        $documentAttributes = $reflectionClass->getAttributes(Document::class);
        if (count($documentAttributes) === 0) {
            throw MappingException::noDocumentAttribute($className);
        }

        /** @var Document $documentAttribute */
        $documentAttribute = $documentAttributes[0]->newInstance();

        $properties = [];
        $idProperty = null;
        $revisionProperty = null;

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $propertyMetadata = $this->parseProperty($reflectionProperty);
            if ($propertyMetadata === null) {
                continue;
            }

            $properties[$propertyMetadata->propertyName] = $propertyMetadata;

            if ($propertyMetadata->isId()) {
                $idProperty = $propertyMetadata;
            } elseif ($propertyMetadata->isRevision()) {
                $revisionProperty = $propertyMetadata;
            }
        }

        return new ClassMetadata(
            className: $className,
            database: $documentAttribute->database,
            type: $documentAttribute->type,
            idProperty: $idProperty,
            revisionProperty: $revisionProperty,
            properties: $properties,
            loadedAt: time(),
        );
    }

    private function parseProperty(ReflectionProperty $property): ?PropertyMetadata
    {
        $propertyName = $property->getName();

        // Check for #[Id] attribute
        $idAttributes = $property->getAttributes(Id::class);
        if (count($idAttributes) > 0) {
            return new PropertyMetadata(
                propertyName: $propertyName,
                fieldName: '_id',
                type: PropertyType::Id,
                nullable: $this->isNullable($property),
                default: null,
            );
        }

        // Check for #[Revision] attribute
        $revisionAttributes = $property->getAttributes(Revision::class);
        if (count($revisionAttributes) > 0) {
            return new PropertyMetadata(
                propertyName: $propertyName,
                fieldName: '_rev',
                type: PropertyType::Revision,
                nullable: true,
                default: null,
            );
        }

        // Check for #[Embedded] attribute
        $embeddedAttributes = $property->getAttributes(Embedded::class);
        if (count($embeddedAttributes) > 0) {
            /** @var Embedded $embedded */
            $embedded = $embeddedAttributes[0]->newInstance();

            return new PropertyMetadata(
                propertyName: $propertyName,
                fieldName: $embedded->name ?? $propertyName,
                type: PropertyType::Embedded,
                nullable: $this->isNullable($property),
                default: null,
                targetClass: $embedded->targetClass,
            );
        }

        // Check for #[EmbeddedCollection] attribute
        $embeddedCollectionAttributes = $property->getAttributes(EmbeddedCollection::class);
        if (count($embeddedCollectionAttributes) > 0) {
            /** @var EmbeddedCollection $embeddedCollection */
            $embeddedCollection = $embeddedCollectionAttributes[0]->newInstance();

            return new PropertyMetadata(
                propertyName: $propertyName,
                fieldName: $embeddedCollection->name ?? $propertyName,
                type: PropertyType::EmbeddedCollection,
                nullable: $this->isNullable($property),
                default: [],
                targetClass: $embeddedCollection->targetClass,
            );
        }

        // Check for #[Field] attribute
        $fieldAttributes = $property->getAttributes(Field::class);
        if (count($fieldAttributes) > 0) {
            /** @var Field $field */
            $field = $fieldAttributes[0]->newInstance();

            // Check for explicit converter or auto-detect from registry
            $converterClass = $field->converter;
            $valueObjectType = null;

            if ($converterClass === null && $this->typeConverterRegistry !== null) {
                $valueObjectType = $this->detectValueObjectType($property);
            }

            // If we have a converter (explicit or detected), use ValueObject type
            if ($converterClass !== null || $valueObjectType !== null) {
                return new PropertyMetadata(
                    propertyName: $propertyName,
                    fieldName: $field->name ?? $propertyName,
                    type: PropertyType::ValueObject,
                    nullable: $field->nullable,
                    default: $field->default,
                    targetClass: $valueObjectType,
                    converterClass: $converterClass, // Only set for explicit converters
                );
            }

            return new PropertyMetadata(
                propertyName: $propertyName,
                fieldName: $field->name ?? $propertyName,
                type: $this->resolveType($property, $field->type),
                nullable: $field->nullable,
                default: $field->default,
            );
        }

        return null;
    }

    /**
     * Detect if a property type is a registered value object type.
     *
     * @return class-string|null
     */
    private function detectValueObjectType(ReflectionProperty $property): ?string
    {
        $type = $property->getType();
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $typeName = $type->getName();

        if ($this->typeConverterRegistry !== null && $this->typeConverterRegistry->has($typeName)) {
            return $typeName;
        }

        return null;
    }

    private function resolveType(ReflectionProperty $property, ?string $typeHint): PropertyType
    {
        // If explicit type is provided, use it
        if ($typeHint !== null) {
            return match ($typeHint) {
                'string' => PropertyType::String,
                'int', 'integer' => PropertyType::Int,
                'float', 'double' => PropertyType::Float,
                'bool', 'boolean' => PropertyType::Bool,
                'array' => PropertyType::Array,
                'datetime' => PropertyType::DateTime,
                default => PropertyType::Mixed,
            };
        }

        // Try to infer from property type
        $type = $property->getType();
        if (!$type instanceof ReflectionNamedType) {
            return PropertyType::Mixed;
        }

        $typeName = $type->getName();

        return match ($typeName) {
            'string' => PropertyType::String,
            'int' => PropertyType::Int,
            'float' => PropertyType::Float,
            'bool' => PropertyType::Bool,
            'array' => PropertyType::Array,
            default => $this->isDateTimeType($typeName) ? PropertyType::DateTime : PropertyType::Mixed,
        };
    }

    private function isDateTimeType(string $typeName): bool
    {
        if ($typeName === DateTimeInterface::class) {
            return true;
        }

        if (!class_exists($typeName) && !interface_exists($typeName)) {
            return false;
        }

        return is_subclass_of($typeName, DateTimeInterface::class);
    }

    private function isNullable(ReflectionProperty $property): bool
    {
        $type = $property->getType();
        if ($type === null) {
            return true;
        }

        return $type->allowsNull();
    }

    private function getCacheKey(string $className): string
    {
        return 'couch_metadata_' . hash('xxh128', $className);
    }

    private function isOutdated(string $className, ClassMetadata $cached): bool
    {
        if (!$this->debug) {
            return false;
        }

        $reflector = new ReflectionClass($className);
        $fileName = $reflector->getFileName();

        if ($fileName === false) {
            return false;
        }

        return filemtime($fileName) > $cached->loadedAt;
    }
}
