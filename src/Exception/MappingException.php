<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Exception;

/**
 * Thrown when there is an error in the document mapping configuration.
 */
final class MappingException extends CouchException
{
    public static function noDocumentAttribute(string $className): self
    {
        return new self(
            sprintf("Class '%s' is not a valid document. Missing #[Document] or #[EmbeddedDocument] attribute.", $className)
        );
    }

    public static function noIdProperty(string $className): self
    {
        return new self(
            sprintf("Document class '%s' must have a property with #[Id] attribute.", $className)
        );
    }

    public static function invalidTargetClass(string $className, string $propertyName): self
    {
        return new self(
            sprintf("Invalid target class for embedded property '%s' in '%s'.", $propertyName, $className)
        );
    }

    public static function classNotFound(string $className): self
    {
        return new self(
            sprintf("Class '%s' does not exist.", $className)
        );
    }
}
