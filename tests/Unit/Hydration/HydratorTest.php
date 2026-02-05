<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Unit\Hydration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\Hydration\Hydrator;
use SmrtSystems\Couch\Tests\Fixtures\UserDocument;

final class HydratorTest extends TestCase
{
    private Hydrator $hydrator;

    protected function setUp(): void
    {
        $this->hydrator = new Hydrator();
    }

    #[Test]
    public function it_hydrates_object_without_constructor(): void
    {
        $values = [
            'id' => 'user-123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ];

        $user = $this->hydrator->hydrate(UserDocument::class, $values);

        $this->assertInstanceOf(UserDocument::class, $user);
        $this->assertSame('user-123', $user->id);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
        $this->assertSame(30, $user->age);
    }

    #[Test]
    public function it_extracts_values_from_object(): void
    {
        $user = new UserDocument();
        $user->id = 'user-123';
        $user->name = 'John Doe';
        $user->email = 'john@example.com';
        $user->age = 30;
        $user->isActive = true;

        $values = $this->hydrator->extract($user);

        $this->assertSame('user-123', $values['id']);
        $this->assertSame('John Doe', $values['name']);
        $this->assertSame('john@example.com', $values['email']);
        $this->assertSame(30, $values['age']);
        $this->assertTrue($values['isActive']);
    }

    #[Test]
    public function it_gets_single_value(): void
    {
        $user = new UserDocument();
        $user->id = 'user-123';
        $user->name = 'John Doe';
        $user->email = 'john@example.com';
        $user->age = 30;

        $this->assertSame('John Doe', $this->hydrator->getValue($user, 'name'));
        $this->assertSame(30, $this->hydrator->getValue($user, 'age'));
    }

    #[Test]
    public function it_sets_single_value(): void
    {
        $user = new UserDocument();
        $user->id = 'user-123';
        $user->name = 'John Doe';
        $user->email = 'john@example.com';
        $user->age = 30;

        $this->hydrator->setValue($user, 'name', 'Jane Doe');
        $this->hydrator->setValue($user, 'age', 25);

        $this->assertSame('Jane Doe', $user->name);
        $this->assertSame(25, $user->age);
    }

    #[Test]
    public function it_returns_null_for_nonexistent_property(): void
    {
        $user = new UserDocument();
        $user->id = 'user-123';
        $user->name = 'John';
        $user->email = 'john@example.com';
        $user->age = 30;

        $this->assertNull($this->hydrator->getValue($user, 'nonexistent'));
    }

    #[Test]
    public function it_ignores_nonexistent_property_when_setting(): void
    {
        $user = new UserDocument();
        $user->id = 'user-123';
        $user->name = 'John';
        $user->email = 'john@example.com';
        $user->age = 30;

        // Should not throw
        $this->hydrator->setValue($user, 'nonexistent', 'value');

        $this->assertSame('John', $user->name);
    }

    #[Test]
    public function it_ignores_unknown_properties_when_hydrating(): void
    {
        $values = [
            'id' => 'user-123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'unknownProperty' => 'should be ignored',
        ];

        $user = $this->hydrator->hydrate(UserDocument::class, $values);

        $this->assertSame('user-123', $user->id);
        $this->assertSame('John Doe', $user->name);
    }
}
