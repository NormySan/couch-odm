<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures;

use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Embedded;
use SmrtSystems\Couch\Attribute\Field;

#[Document(database: '_embedded')]
class AddressEmbedded
{
    #[Field]
    public string $street;

    #[Field]
    public string $city;

    #[Field]
    public string $country;

    #[Field(name: 'postal_code')]
    public ?string $postalCode = null;

    #[Embedded(targetClass: GeoLocationEmbedded::class)]
    public ?GeoLocationEmbedded $location = null;
}
