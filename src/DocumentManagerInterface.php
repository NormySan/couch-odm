<?php

declare(strict_types=1);

namespace SmrtSystems\Couch;

use SmrtSystems\Couch\Query\FindQuery;
use SmrtSystems\Couch\Query\AllQuery;
use SmrtSystems\Couch\Query\ViewQuery;

/**
 * Interface for the document manager.
 */
interface DocumentManagerInterface
{
    /**
     * Get a document by its ID.
     *
     * @template TDocument of object
     *
     * @param class-string<TDocument> $className
     *
     * @return TDocument|null
     */
    public function get(string $className, string $id): ?object;

    /**
     * Find documents using Mango query.
     *
     * @template T of object
     * @param class-string<T> $className
     * @return iterable<T>
     */
    public function findBy(string $className, FindQuery $query): iterable;

    /**
     * Find documents by ID range.
     *
     * @template T of object
     * @param class-string<T> $className
     * @return iterable<T>
     */
    public function all(string $className, AllQuery $query): iterable;

    /**
     * Find documents using a view.
     *
     * @template T of object
     * @param class-string<T> $className
     * @return iterable<T>
     */
    public function findByView(string $className, ViewQuery $query): iterable;

    /**
     * Mark a document for persistence.
     */
    public function persist(object $document): void;

    /**
     * Mark a document for removal.
     */
    public function remove(object $document): void;

    /**
     * Flush all pending changes to the database.
     */
    public function flush(): void;

    /**
     * Clear the identity map and pending changes.
     */
    public function clear(): void;

    /**
     * Refresh a document from the database.
     *
     * @template T of object
     * @param T $document
     * @return T
     */
    public function refresh(object $document): object;

    /**
     * Detach a document from the manager.
     */
    public function detach(object $document): void;
}
