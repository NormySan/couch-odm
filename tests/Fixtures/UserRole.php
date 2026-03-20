<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Fixtures;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';
}
