<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Cache;

/**
 * Interface for document cache implementations.
 */
interface DocumentCacheInterface
{
    /**
     * Get a document from cache.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $database, string $id): ?array;

    /**
     * Store a document in cache.
     *
     * @param array<string, mixed> $data
     */
    public function set(string $database, string $id, array $data, ?int $ttl = null): void;

//    /**
//     * Remove a document from cache.
//     */
//    public function delete(string $database, string $id): void;
//
//    /**
//     * Invalidate all documents in a database.
//     */
//    public function invalidateDatabase(string $database): void;
//
//    /**
//     * Check if document exists in cache.
//     */
//    public function has(string $database, string $id): bool;
//
//    /**
//     * Get multiple documents from cache.
//     *
//     * @param string[] $ids
//     * @return array<string, array<string, mixed>> Keyed by document ID
//     */
//    public function getMultiple(string $database, array $ids): array;
//
//    /**
//     * Clear all cached documents.
//     */
//    public function clear(): void;
}
