<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures;

use DateTimeImmutable;
use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Field;
use SmrtSystems\Couch\Attribute\Id;

#[Document('characters')]
class Character
{
    #[Id]
    public readonly string $id;

    #[Field]
    public string $name;

    #[Field]
    public string $role;

    #[Field]
    public DateTimeImmutable $birthday;

    public function __construct(
        string $id,
        string $name,
        string $role,
        DateTimeImmutable $birthday = new DateTimeImmutable(),
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->role = $role;
        $this->birthday = $birthday;
    }
}