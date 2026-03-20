<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures;

use DateTimeImmutable;
use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Field;
use SmrtSystems\Couch\Attribute\Id;

#[Document('users')]
class User
{
    #[Id]
    public readonly string $id;

    #[Field]
    public string $name;

    #[Field]
    public string $email;

    #[Field]
    public string $role;

    #[Field]
    public DateTimeImmutable $birthday;

    #[Field]
    public readonly DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $name,
        string $email = 'test@example.com',
        string $role = 'user',
        DateTimeImmutable $birthday = new DateTimeImmutable(),
        DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->role = $role;
        $this->birthday = $birthday;
        $this->createdAt = $createdAt;
    }
}
