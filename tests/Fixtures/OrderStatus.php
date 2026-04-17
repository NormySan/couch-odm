<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures;

enum OrderStatus: int
{
    case Pending = 0;
    case Processing = 1;
    case Shipped = 2;
    case Delivered = 3;
}
