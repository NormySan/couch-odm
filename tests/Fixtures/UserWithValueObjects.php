<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures;

use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Field;
use SmrtSystems\Couch\Attribute\Id;
use SmrtSystems\Couch\Tests\Fixtures\Converter\EmailConverter;
use SmrtSystems\Couch\Tests\Fixtures\ValueObject\Email;
use SmrtSystems\Couch\Tests\Fixtures\ValueObject\Money;

#[Document(database: 'users')]
class UserWithValueObjects
{
    #[Id]
    public string $id;

    #[Field]
    public string $name;

    #[Field] // Auto-detected via registry
    public Email $email;

    #[Field] // Auto-detected via registry
    public Money $balance;

    #[Field(converter: EmailConverter::class)] // Explicit converter
    public ?Email $backupEmail = null;
}
