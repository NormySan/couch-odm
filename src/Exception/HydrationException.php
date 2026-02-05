<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Exception;

use Throwable;

/**
 * Thrown when there is an error hydrating or extracting document data.
 */
final class HydrationException extends CouchException
{
    public static function cannotInstantiate(string $className, Throwable $previous): self
    {
        return new self(
            sprintf("Cannot instantiate '%s': %s", $className, $previous->getMessage()),
            0,
            $previous
        );
    }

    public static function typeMismatch(string $className, string $property, string $expected, string $actual): self
    {
        return new self(
            sprintf(
                "Type mismatch for %s::%s. Expected %s, got %s.",
                $className,
                $property,
                $expected,
                $actual
            )
        );
    }

    public static function invalidData(string $className, string $property, string $reason): self
    {
        return new self(
            sprintf(
                "Invalid data for %s::%s: %s",
                $className,
                $property,
                $reason
            )
        );
    }
}
