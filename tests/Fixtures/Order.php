<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures;

use DateTimeImmutable;
use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Field;
use SmrtSystems\Couch\Attribute\Id;

#[Document('orders')]
class Order
{
    #[Id]
    public readonly string $id;

    #[Field]
    public readonly string $number;

    #[Field]
    public DateTimeImmutable $date;

    public function __construct(
        string            $number,
        DateTimeImmutable $date = new DateTimeImmutable(),
    ) {
        $this->id = sprintf(
            'order_%s_%s',
            $number,
            $date->format('Y-m-d'),
        );

        $this->number = $number;
        $this->date = $date;
    }
}