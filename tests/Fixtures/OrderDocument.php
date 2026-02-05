<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures;

use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\EmbeddedCollection;
use SmrtSystems\Couch\Attribute\Field;
use SmrtSystems\Couch\Attribute\Id;
use SmrtSystems\Couch\Attribute\Revision;

#[Document(database: 'orders', type: 'order')]
class OrderDocument
{
    #[Id]
    public string $id;

    #[Revision]
    public ?string $rev = null;

    #[Field(name: 'customer_id')]
    public string $customerId;

    #[Field]
    public string $status = 'pending';

    #[Field]
    public float $total = 0.0;

    #[EmbeddedCollection(targetClass: LineItemEmbedded::class, name: 'line_items')]
    public array $lineItems = [];

    #[Field(type: 'datetime', name: 'created_at')]
    public ?\DateTimeImmutable $createdAt = null;
}
