<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Document;

use DateTimeImmutable;
use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Field;
use SmrtSystems\Couch\Attribute\Id;
use SmrtSystems\Couch\Attribute\Revision;

#[Document('users')]
class User {

    #[Id]
    public readonly string $id;

    #[Revision]
    public string $rev;

    #[Field]
    public string $name;

    #[Field]
    public readonly DateTimeImmutable $createdAt;

    public function __construct(string $name) {
        $this->name = $name;
        $this->createdAt = new DateTimeImmutable();
    }

}