<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Type;

/**
 * Interface for custom type converters.
 *
 * Type converters handle the conversion between PHP value objects
 * and their database representation.
 *
 * @template T of object
 */
interface TypeConverterInterface
{
    /**
     * Returns the fully qualified class name of the PHP type this converter handles.
     *
     * @return class-string<T>
     */
    public function getPhpType(): string;

    /**
     * Converts a PHP value object to its database representation.
     *
     * @param T|null $value The PHP value object to convert
     * @return mixed The database representation (scalar, array, or null)
     */
    public function toDatabaseValue(mixed $value): mixed;

    /**
     * Converts a database value to a PHP value object.
     *
     * @param mixed $value The database value to convert
     * @return T|null The PHP value object
     */
    public function toPhpValue(mixed $value): mixed;
}
