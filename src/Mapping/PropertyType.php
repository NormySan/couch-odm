<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Mapping;

/**
 * Represents the type of a mapped property.
 */
enum PropertyType: string
{
    case Id = 'id';
    case Revision = 'revision';
    case String = 'string';
    case Int = 'int';
    case Float = 'float';
    case Bool = 'bool';
    case Array = 'array';
    case DateTime = 'datetime';
    case Embedded = 'embedded';
    case EmbeddedCollection = 'embedded_collection';
    case ValueObject = 'value_object';
    case Mixed = 'mixed';
}
