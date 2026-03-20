<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Hydration;

/**
 * Interface for document mappers that convert between CouchDB data and PHP objects.
 */
interface DocumentMapperInterface
{
    /**
     * Convert CouchDB document array to PHP object.
     *
     * @template T of object
     * @param class-string<T> $className
     * @param array<string, mixed> $data
     * @return T
     */
    public function toDocument(string $className, array $data): object;

    /**
     * Convert PHP object to CouchDB document array.
     *
     * @param object $document
     * @return array<string, mixed>
     */
    public function toArray(object $document): array;

    /**
     * Get the database name for a document class.
     *
     * @param class-string $className
     */
    public function getDatabase(string $className): string;

    /**
     * Get the document ID from an object.
     */
    public function getId(object $document): ?string;

    /**
     * Set the document ID on an object.
     */
    public function setId(object $document, string $id): void;

}
