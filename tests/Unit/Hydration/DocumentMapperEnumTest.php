<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Unit\Hydration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\Hydration\DocumentMapper;
use SmrtSystems\Couch\Hydration\Hydrator;
use SmrtSystems\Couch\Mapping\MetadataFactory;
use SmrtSystems\Couch\Tests\Fixtures\OrderDocument;
use SmrtSystems\Couch\Tests\Fixtures\OrderStatus;
use SmrtSystems\Couch\Tests\Fixtures\UserDocument;
use SmrtSystems\Couch\Tests\Fixtures\UserRole;

final class DocumentMapperEnumTest extends TestCase
{
    private DocumentMapper $mapper;

    protected function setUp(): void
    {
        $metadataFactory = new MetadataFactory();
        $hydrator = new Hydrator();
        $this->mapper = new DocumentMapper($metadataFactory, $hydrator);
    }

    #[Test]
    public function it_hydrates_string_backed_enum(): void
    {
        $data = [
            '_id' => 'user-1',
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'is_active' => true,
            'age' => 25,
            'role' => 'admin',
        ];

        $user = $this->mapper->toDocument(UserDocument::class, $data);

        $this->assertSame(UserRole::Admin, $user->role);
    }

    #[Test]
    public function it_hydrates_int_backed_enum(): void
    {
        $data = [
            '_id' => 'order-1',
            'customer_id' => 'cust-1',
            'status' => 2,
            'total' => 100.0,
        ];

        $order = $this->mapper->toDocument(OrderDocument::class, $data);

        $this->assertSame(OrderStatus::Shipped, $order->status);
    }

    #[Test]
    public function it_hydrates_nullable_enum_as_null(): void
    {
        $data = [
            '_id' => 'user-2',
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'is_active' => true,
            'age' => 30,
            'role' => null,
        ];

        $user = $this->mapper->toDocument(UserDocument::class, $data);

        $this->assertNull($user->role);
    }

    #[Test]
    public function it_hydrates_missing_enum_as_null(): void
    {
        $data = [
            '_id' => 'user-3',
            'name' => 'Charlie',
            'email' => 'charlie@example.com',
            'is_active' => true,
            'age' => 28,
        ];

        $user = $this->mapper->toDocument(UserDocument::class, $data);

        $this->assertNull($user->role);
    }

    #[Test]
    public function it_returns_null_for_invalid_enum_value(): void
    {
        $data = [
            '_id' => 'user-4',
            'name' => 'Dave',
            'email' => 'dave@example.com',
            'is_active' => true,
            'age' => 35,
            'role' => 'nonexistent',
        ];

        $user = $this->mapper->toDocument(UserDocument::class, $data);

        $this->assertNull($user->role);
    }

    #[Test]
    public function it_extracts_string_backed_enum(): void
    {
        $user = new UserDocument();
        $user->id = 'user-1';
        $user->name = 'Alice';
        $user->email = 'alice@example.com';
        $user->age = 25;
        $user->role = UserRole::User;

        $data = $this->mapper->toArray($user);

        $this->assertSame('user', $data['role']);
    }

    #[Test]
    public function it_extracts_int_backed_enum(): void
    {
        $order = new OrderDocument();
        $order->id = 'order-1';
        $order->customerId = 'cust-1';
        $order->status = OrderStatus::Delivered;
        $order->total = 50.0;

        $data = $this->mapper->toArray($order);

        $this->assertSame(3, $data['status']);
    }

    #[Test]
    public function it_extracts_null_enum(): void
    {
        $user = new UserDocument();
        $user->id = 'user-1';
        $user->name = 'Alice';
        $user->email = 'alice@example.com';
        $user->age = 25;
        $user->role = null;

        $data = $this->mapper->toArray($user);

        $this->assertArrayNotHasKey('role', $data);
    }

    #[Test]
    public function it_roundtrips_enum_value(): void
    {
        $user = new UserDocument();
        $user->id = 'user-1';
        $user->name = 'Alice';
        $user->email = 'alice@example.com';
        $user->age = 25;
        $user->role = UserRole::Admin;

        $data = $this->mapper->toArray($user);
        $hydrated = $this->mapper->toDocument(UserDocument::class, $data);

        $this->assertSame(UserRole::Admin, $hydrated->role);
    }
}
