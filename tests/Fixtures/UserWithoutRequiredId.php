<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures;

use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Field;
use SmrtSystems\Couch\Attribute\Id;

#[Document('users')]
class UserWithoutRequiredId
{
    #[Id]
    public readonly string $id;

    #[Field]
    public string $name;

    public function __construct(
        string $name,
    ) {
        $this->name = $name;
    }
}