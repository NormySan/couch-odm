<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Unit\Hydration;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\Hydration\DocumentMapper;
use SmrtSystems\Couch\Hydration\Hydrator;
use SmrtSystems\Couch\Mapping\MetadataFactory;
use SmrtSystems\Couch\Tests\Fixtures\AddressEmbedded;
use SmrtSystems\Couch\Tests\Fixtures\LineItemEmbedded;
use SmrtSystems\Couch\Tests\Fixtures\OrderDocument;
use SmrtSystems\Couch\Tests\Fixtures\UserDocument;

final class DocumentMapperTest extends TestCase
{
    private DocumentMapper $mapper;

    protected function setUp(): void
    {
        $metadataFactory = new MetadataFactory();
        $hydrator = new Hydrator();
        $this->mapper = new DocumentMapper($metadataFactory, $hydrator);
    }

    #[Test]
    public function it_hydrates_simple_properties(): void
    {
        $data = [
            '_id' => 'user-123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_active' => true,
            'age' => 30,
        ];

        $user = $this->mapper->toDocument(UserDocument::class, $data);

        $this->assertInstanceOf(UserDocument::class, $user);
        $this->assertSame('user-123', $user->id);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
        $this->assertTrue($user->isActive);
        $this->assertSame(30, $user->age);
    }

    #[Test]
    public function it_hydrates_datetime_fields(): void
    {
        $data = [
            '_id' => 'user-123',
            'name' => 'John',
            'email' => 'john@example.com',
            'is_active' => true,
            'age' => 30,
            'createdAt' => '2024-01-15T10:30:00+00:00',
        ];

        $user = $this->mapper->toDocument(UserDocument::class, $data);

        $this->assertInstanceOf(DateTimeImmutable::class, $user->createdAt);
        $this->assertSame('2024-01-15', $user->createdAt->format('Y-m-d'));
    }

    #[Test]
    public function it_hydrates_embedded_objects(): void
    {
        $data = [
            '_id' => 'user-123',
            'name' => 'John',
            'email' => 'john@example.com',
            'is_active' => true,
            'age' => 30,
            'address' => [
                'street' => '123 Main St',
                'city' => 'Boston',
                'country' => 'USA',
                'postal_code' => '02101',
            ],
        ];

        $user = $this->mapper->toDocument(UserDocument::class, $data);

        $this->assertInstanceOf(AddressEmbedded::class, $user->address);
        $this->assertSame('123 Main St', $user->address->street);
        $this->assertSame('Boston', $user->address->city);
        $this->assertSame('USA', $user->address->country);
        $this->assertSame('02101', $user->address->postalCode);
    }

    #[Test]
    public function it_hydrates_nested_embedded_objects(): void
    {
        $data = [
            '_id' => 'user-123',
            'name' => 'John',
            'email' => 'john@example.com',
            'is_active' => true,
            'age' => 30,
            'address' => [
                'street' => '123 Main St',
                'city' => 'Boston',
                'country' => 'USA',
                'location' => [
                    'lat' => 42.3601,
                    'lng' => -71.0589,
                ],
            ],
        ];

        $user = $this->mapper->toDocument(UserDocument::class, $data);

        $this->assertNotNull($user->address->location);
        $this->assertSame(42.3601, $user->address->location->lat);
        $this->assertSame(-71.0589, $user->address->location->lng);
    }

    #[Test]
    public function it_hydrates_embedded_collections(): void
    {
        $data = [
            '_id' => 'user-123',
            'name' => 'John',
            'email' => 'john@example.com',
            'is_active' => true,
            'age' => 30,
            'additionalAddresses' => [
                ['street' => '456 Oak Ave', 'city' => 'Cambridge', 'country' => 'USA'],
                ['street' => '789 Pine Rd', 'city' => 'Newton', 'country' => 'USA'],
            ],
        ];

        $user = $this->mapper->toDocument(UserDocument::class, $data);

        $this->assertCount(2, $user->additionalAddresses);
        $this->assertInstanceOf(AddressEmbedded::class, $user->additionalAddresses[0]);
        $this->assertSame('456 Oak Ave', $user->additionalAddresses[0]->street);
        $this->assertSame('789 Pine Rd', $user->additionalAddresses[1]->street);
    }

    #[Test]
    public function it_extracts_simple_properties(): void
    {
        $user = new UserDocument();
        $user->id = 'user-123';
        $user->name = 'John Doe';
        $user->email = 'john@example.com';
        $user->isActive = true;
        $user->age = 30;

        $data = $this->mapper->toArray($user);

        $this->assertSame('user-123', $data['_id']);
        $this->assertArrayNotHasKey('_rev', $data);
        $this->assertSame('John Doe', $data['name']);
        $this->assertSame('john@example.com', $data['email']);
        $this->assertTrue($data['is_active']);
        $this->assertSame(30, $data['age']);
    }

    #[Test]
    public function it_extracts_datetime_fields(): void
    {
        $user = new UserDocument();
        $user->id = 'user-123';
        $user->name = 'John';
        $user->email = 'john@example.com';
        $user->age = 30;
        $user->createdAt = new DateTimeImmutable('2024-01-15T10:30:00+00:00');

        $data = $this->mapper->toArray($user);

        $this->assertSame('2024-01-15T10:30:00+00:00', $data['createdAt']);
    }

    #[Test]
    public function it_extracts_embedded_objects(): void
    {
        $user = new UserDocument();
        $user->id = 'user-123';
        $user->name = 'John';
        $user->email = 'john@example.com';
        $user->age = 30;

        $address = new AddressEmbedded();
        $address->street = '123 Main St';
        $address->city = 'Boston';
        $address->country = 'USA';
        $user->address = $address;

        $data = $this->mapper->toArray($user);

        $this->assertIsArray($data['address']);
        $this->assertSame('123 Main St', $data['address']['street']);
        $this->assertSame('Boston', $data['address']['city']);
    }

    #[Test]
    public function it_extracts_embedded_collections(): void
    {
        $order = new OrderDocument();
        $order->id = 'order-123';
        $order->customerId = 'cust-456';
        $order->status = \SmrtSystems\Couch\Tests\Fixtures\OrderStatus::Pending;
        $order->total = 99.99;

        $item1 = new LineItemEmbedded();
        $item1->productId = 'prod-1';
        $item1->productName = 'Widget';
        $item1->quantity = 2;
        $item1->unitPrice = 25.00;

        $item2 = new LineItemEmbedded();
        $item2->productId = 'prod-2';
        $item2->productName = 'Gadget';
        $item2->quantity = 1;
        $item2->unitPrice = 49.99;

        $order->lineItems = [$item1, $item2];

        $data = $this->mapper->toArray($order);

        $this->assertIsArray($data['line_items']);
        $this->assertCount(2, $data['line_items']);
        $this->assertSame('prod-1', $data['line_items'][0]['product_id']);
        $this->assertSame('Widget', $data['line_items'][0]['product_name']);
        $this->assertSame(2, $data['line_items'][0]['quantity']);
    }

    #[Test]
    public function it_returns_database_name(): void
    {
        $this->assertSame('users', $this->mapper->getDatabase(UserDocument::class));
        $this->assertSame('orders', $this->mapper->getDatabase(OrderDocument::class));
    }

    #[Test]
    public function it_returns_document_id(): void
    {
        $user = new UserDocument();
        $user->id = 'user-123';
        $user->name = 'John';
        $user->email = 'john@example.com';
        $user->age = 30;

        $this->assertSame('user-123', $this->mapper->getId($user));
    }

    #[Test]
    public function it_handles_null_embedded_object(): void
    {
        $data = [
            '_id' => 'user-123',
            'name' => 'John',
            'email' => 'john@example.com',
            'is_active' => true,
            'age' => 30,
            'address' => null,
        ];

        $user = $this->mapper->toDocument(UserDocument::class, $data);

        $this->assertNull($user->address);
    }

    #[Test]
    public function it_handles_empty_embedded_collection(): void
    {
        $data = [
            '_id' => 'user-123',
            'name' => 'John',
            'email' => 'john@example.com',
            'is_active' => true,
            'age' => 30,
            'additionalAddresses' => [],
        ];

        $user = $this->mapper->toDocument(UserDocument::class, $data);

        $this->assertSame([], $user->additionalAddresses);
    }
}
