<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures;

use SmrtSystems\Couch\Attribute\EmbeddedDocument;
use SmrtSystems\Couch\Attribute\Field;

#[EmbeddedDocument]
class GeoLocationEmbedded
{
    #[Field]
    public float $lat;

    #[Field]
    public float $lng;
}
