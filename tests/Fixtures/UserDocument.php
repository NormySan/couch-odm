<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures;

use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Embedded;
use SmrtSystems\Couch\Attribute\EmbeddedCollection;
use SmrtSystems\Couch\Attribute\Field;
use SmrtSystems\Couch\Attribute\Id;
use SmrtSystems\Couch\Attribute\Revision;

#[Document(database: 'users')]
class UserDocument
{
    #[Id]
    public string $id;

    #[Revision]
    public ?string $rev = null;

    #[Field]
    public string $name;

    #[Field]
    public string $email;

    #[Field(name: 'is_active')]
    public bool $isActive = true;

    #[Field]
    public int $age;

    #[Field(type: 'datetime')]
    public ?\DateTimeImmutable $createdAt = null;

    #[Embedded(targetClass: AddressEmbedded::class)]
    public ?AddressEmbedded $address = null;

    #[EmbeddedCollection(targetClass: AddressEmbedded::class)]
    public array $additionalAddresses = [];
}
