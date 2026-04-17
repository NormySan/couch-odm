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
    public readonly string $status;

    #[Field]
    public readonly string $customerId;

    #[Field]
    public DateTimeImmutable $date;

    public function __construct(
        string $number,
        string $status = 'pending',
        string $customerId = '1',
        DateTimeImmutable $date = new DateTimeImmutable(),
    ) {
        $this->id = sprintf(
            'order_%s_%s',
            $date->format('Y-m-d'),
            $number,
        );

        $this->number = $number;
        $this->status = $status;
        $this->customerId = $customerId;
        $this->date = $date;
    }
}