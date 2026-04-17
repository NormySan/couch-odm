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
     * @param class-string<TDocument> $className The fully qualified class name of the document class.
     * @param string $id The ID of the document to find.
     *
     * @return TDocument|null
     */
    public function get(string $className, string $id): ?object;

    /**
     * Find documents based on a Mango query.
     *
     * @template TDocument of object
     *
     * @param class-string<TDocument> $className The fully qualified class name of the document class.
     * @param FindQuery $query The query to execute.
     *
     * @return iterable<TDocument>
     */
    public function findBy(string $className, FindQuery $query): iterable;

    /**
     * Get a batch of documents based on a range or list of IDs.
     *
     * @template TDocument of object
     *
     * @param class-string<TDocument> $className The fully qualified class name of the document class.
     * @param AllQuery $query The range query to execute.
     *
     * @return iterable<TDocument>
     */
    public function all(string $className, AllQuery $query): iterable;

    /**
     * Find documents based on a view.
     *
     * @template TDocument of object
     *
     * @param class-string<TDocument> $className The fully qualified class name of the document class.
     * @param ViewQuery $query The view query to execute.
     *
     * @return iterable<TDocument>
     */
    public function view(string $className, ViewQuery $query): iterable;

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
