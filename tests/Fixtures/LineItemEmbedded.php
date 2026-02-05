<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures;

use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Field;

#[Document(database: '_embedded')]
class LineItemEmbedded
{
    #[Field(name: 'product_id')]
    public string $productId;

    #[Field(name: 'product_name')]
    public string $productName;

    #[Field]
    public int $quantity;

    #[Field(name: 'unit_price')]
    public float $unitPrice;
}
