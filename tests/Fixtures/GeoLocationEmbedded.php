<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures;

use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Field;

#[Document(database: '_embedded')]
class GeoLocationEmbedded
{
    #[Field]
    public float $lat;

    #[Field]
    public float $lng;
}
