<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Client;

use SmrtSystems\Couch\Client\Data\CreateDesignDocumentInput;
use SmrtSystems\Couch\Client\Response\BulkResponse;
use SmrtSystems\Couch\Client\Response\DocumentResponse;

/**
 * Interface for CouchDB HTTP client implementations.
 *
 * @phpstan-type DesignDocumentArguments array{
 *     language?: 'erlang'|'javascript',
 * }
 */
interface CouchDbClientInterface
{
    /**
     * Get a single document by ID.
     */
    public function get(string $database, string $id): DocumentResponse;

    /**
     * Create or update a document.
     *
     * @param array<string, mixed> $data
     */
    public function put(string $database, string $id, array $data): DocumentResponse;

    /**
     * Delete a document.
     */
    public function delete(string $database, string $id, string $rev): bool;

    /**
     * Execute a Mango query using _find.
     *
     * @param array<string, mixed> $selector
     * @param array<string, mixed> $options limit, skip, sort, fields, bookmark
     * @return iterable<array<string, mixed>> Yields document arrays
     */
    public function find(string $database, array $selector, array $options = []): iterable;

    /**
     * Query _all_docs endpoint for range queries.
     *
     * @param array<string, mixed> $options include_docs, startkey, endkey, keys, etc.
     * @return iterable<array<string, mixed>> Yields document arrays
     */
    public function allDocs(string $database, array $options = []): iterable;

    /**
     * Query a view.
     *
     * @param array<string, mixed> $options key, keys, startkey, endkey, reduce, etc.
     * @return iterable<array<string, mixed>> Yields row arrays
     */
    public function view(string $database, string $design, string $view, array $options = []): iterable;

    /**
     * Create a design document.
     */
    public function createDesignDocument(CreateDesignDocumentInput $input): array;

    /**
     * Remove a design document.
     */
    public function deleteDesignDocument(string $database, string $name): void;

    /**
     * Bulk document operations.
     *
     * @param array<array<string, mixed>> $docs
     */
    public function bulk(string $database, array $docs): BulkResponse;

    /**
     * Check if database exists.
     */
    public function databaseExists(string $database): bool;

    /**
     * Create a database.
     */
    public function createDatabase(string $database): bool;

    /**
     * Delete a database.
     */
    public function deleteDatabase(string $database): bool;
}
