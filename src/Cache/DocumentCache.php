<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Cache;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Manages caching of documents
 *
 * This class is a simple wrapper around the provided cache that manages the
 * caching of documents, creating the cache keys and more.
 *
 * @todo Should we handle the InvalidArgumentException thrown by the cache?
 */
final class DocumentCache implements DocumentCacheInterface {

    public function __construct(
        private readonly ?CacheInterface $cache = null,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function get(string $database, string $id): ?array {
        return $this->cache?->get(
            key: $this->getCacheKey($database, $id)
        ) ?? null;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function set(string $database, string $id, array $data, ?int $ttl = null): void {
        $this->cache?->set(
            key: $this->getCacheKey($database, $id),
            value: $data,
            ttl: $ttl
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function delete(string $database, string $id): void {
        $this->cache?->delete(
            key: $this->getCacheKey($database, $id)
        );
    }

    private function getCacheKey(string $database, string $id): string {
        return "doc.$database.$id";
    }

//    public function invalidateDatabase(string $database): void
//    {
//        $this->cache->invalidateTags([$this->getDatabaseTag($database)]);
//    }
//
//    public function has(string $database, string $id): bool
//    {
//        return $this->cache->hasItem($this->getCacheKey($database, $id));
//    }
//
//    public function getMultiple(string $database, array $ids): array
//    {
//        if ($ids === []) {
//            return [];
//        }
//
//        $keyToId = [];
//        foreach ($ids as $id) {
//            $key = $this->getCacheKey($database, $id);
//            $keyToId[$key] = $id;
//        }
//
//        $results = [];
//        foreach ($this->cache->getItems(array_keys($keyToId)) as $key => $item) {
//            if ($item->isHit()) {
//                $value = $item->get();
//                if (is_array($value)) {
//                    $id = $keyToId[$key];
//                    $results[$id] = $value;
//                }
//            }
//        }
//
//        return $results;
//    }
//
//    public function clear(): void
//    {
//        $this->cache->clear();
//        $this->keyToIdMap = [];
//    }
//
//    private function getCacheKey(string $database, string $id): string
//    {
//        // Use a hash to ensure valid cache key characters
//        return sprintf('doc.%s.%s', $this->sanitizeKey($database), hash('xxh128', $id));
//    }
//
//    private function getDatabaseTag(string $database): string
//    {
//        return sprintf('db.%s', $this->sanitizeKey($database));
//    }
//
//    private function getDocumentTag(string $database, string $id): string
//    {
//        return sprintf('doc.%s.%s', $this->sanitizeKey($database), hash('xxh128', $id));
//    }
//
//    /**
//     * Sanitize a string to be a valid cache key component.
//     */
//    private function sanitizeKey(string $key): string
//    {
//        return preg_replace('/[^a-zA-Z0-9_.-]/', '_', $key) ?? $key;
//    }
}
