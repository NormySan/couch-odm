<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Unit\Mapping;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\Exception\MappingException;
use SmrtSystems\Couch\Mapping\MetadataFactory;
use SmrtSystems\Couch\Mapping\PropertyType;
use SmrtSystems\Couch\Tests\Fixtures\AddressEmbedded;
use SmrtSystems\Couch\Tests\Fixtures\OrderDocument;
use SmrtSystems\Couch\Tests\Fixtures\UserDocument;

final class MetadataFactoryTest extends TestCase
{
    private MetadataFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new MetadataFactory();
    }

    #[Test]
    public function it_parses_document_attribute(): void
    {
        $metadata = $this->factory->getMetadataFor(UserDocument::class);

        $this->assertSame(UserDocument::class, $metadata->className);
        $this->assertSame('users', $metadata->database);
        $this->assertNull($metadata->type);
    }

    #[Test]
    public function it_parses_document_with_type(): void
    {
        $metadata = $this->factory->getMetadataFor(OrderDocument::class);

        $this->assertSame('orders', $metadata->database);
        $this->assertSame('order', $metadata->type);
    }

    #[Test]
    public function it_parses_id_property(): void
    {
        $metadata = $this->factory->getMetadataFor(UserDocument::class);

        $this->assertNotNull($metadata->idProperty);
        $this->assertSame('id', $metadata->idProperty->propertyName);
        $this->assertSame('_id', $metadata->idProperty->fieldName);
        $this->assertSame(PropertyType::Id, $metadata->idProperty->type);
    }

    #[Test]
    public function it_parses_simple_field_properties(): void
    {
        $metadata = $this->factory->getMetadataFor(UserDocument::class);

        $nameProperty = $metadata->getProperty('name');
        $this->assertNotNull($nameProperty);
        $this->assertSame('name', $nameProperty->fieldName);
        $this->assertSame(PropertyType::String, $nameProperty->type);

        $ageProperty = $metadata->getProperty('age');
        $this->assertNotNull($ageProperty);
        $this->assertSame(PropertyType::Int, $ageProperty->type);

        $isActiveProperty = $metadata->getProperty('isActive');
        $this->assertNotNull($isActiveProperty);
        $this->assertSame('is_active', $isActiveProperty->fieldName);
        $this->assertSame(PropertyType::Bool, $isActiveProperty->type);
    }

    #[Test]
    public function it_parses_datetime_field(): void
    {
        $metadata = $this->factory->getMetadataFor(UserDocument::class);

        $createdAtProperty = $metadata->getProperty('createdAt');
        $this->assertNotNull($createdAtProperty);
        $this->assertSame(PropertyType::DateTime, $createdAtProperty->type);
        $this->assertTrue($createdAtProperty->nullable);
    }

    #[Test]
    public function it_parses_embedded_property(): void
    {
        $metadata = $this->factory->getMetadataFor(UserDocument::class);

        $addressProperty = $metadata->getProperty('address');
        $this->assertNotNull($addressProperty);
        $this->assertSame(PropertyType::Embedded, $addressProperty->type);
        $this->assertSame(AddressEmbedded::class, $addressProperty->targetClass);
        $this->assertTrue($addressProperty->nullable);
    }

    #[Test]
    public function it_parses_embedded_collection_property(): void
    {
        $metadata = $this->factory->getMetadataFor(UserDocument::class);

        $additionalAddressesProperty = $metadata->getProperty('additionalAddresses');
        $this->assertNotNull($additionalAddressesProperty);
        $this->assertSame(PropertyType::EmbeddedCollection, $additionalAddressesProperty->type);
        $this->assertSame(AddressEmbedded::class, $additionalAddressesProperty->targetClass);
    }

    #[Test]
    public function it_parses_embedded_collection_with_custom_name(): void
    {
        $metadata = $this->factory->getMetadataFor(OrderDocument::class);

        $lineItemsProperty = $metadata->getProperty('lineItems');
        $this->assertNotNull($lineItemsProperty);
        $this->assertSame('line_items', $lineItemsProperty->fieldName);
        $this->assertSame(PropertyType::EmbeddedCollection, $lineItemsProperty->type);
    }

    #[Test]
    public function it_parses_embedded_document_attribute(): void
    {
        $metadata = $this->factory->getMetadataFor(AddressEmbedded::class);

        $this->assertSame(AddressEmbedded::class, $metadata->className);
        $this->assertNull($metadata->database);
        $this->assertNull($metadata->type);
        $this->assertNull($metadata->idProperty);
    }

    #[Test]
    public function it_throws_for_class_without_document_attribute(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Missing #[Document] or #[EmbeddedDocument] attribute');

        $this->factory->getMetadataFor(\stdClass::class);
    }

    #[Test]
    public function it_throws_for_nonexistent_class(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('does not exist');

        $this->factory->getMetadataFor('NonExistentClass');
    }

    #[Test]
    public function it_caches_metadata_in_memory(): void
    {
        $metadata1 = $this->factory->getMetadataFor(UserDocument::class);
        $metadata2 = $this->factory->getMetadataFor(UserDocument::class);

        $this->assertSame($metadata1, $metadata2);
    }

    #[Test]
    public function it_checks_if_class_has_metadata(): void
    {
        $this->assertTrue($this->factory->hasMetadataFor(UserDocument::class));
        $this->assertFalse($this->factory->hasMetadataFor(\stdClass::class));
    }

    #[Test]
    public function it_returns_field_mapping(): void
    {
        $metadata = $this->factory->getMetadataFor(UserDocument::class);
        $fieldMapping = $metadata->getFieldMapping();

        $this->assertSame('id', $fieldMapping['_id']);
        $this->assertArrayNotHasKey('_rev', $fieldMapping);
        $this->assertSame('isActive', $fieldMapping['is_active']);
    }

    #[Test]
    public function it_returns_property_mapping(): void
    {
        $metadata = $this->factory->getMetadataFor(UserDocument::class);
        $propertyMapping = $metadata->getPropertyMapping();

        $this->assertSame('_id', $propertyMapping['id']);
        $this->assertArrayNotHasKey('rev', $propertyMapping);
        $this->assertSame('is_active', $propertyMapping['isActive']);
    }
}
