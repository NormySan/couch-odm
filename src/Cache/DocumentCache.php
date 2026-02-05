<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Two-layer document cache using Symfony Cache.
 *
 * Layer 1: ArrayAdapter (request-scoped, in-memory)
 * Layer 2: Optional external cache (Redis, etc.)
 */
final class DocumentCache implements DocumentCacheInterface
{
    private readonly TagAwareAdapter $cache;

    /** @var array<string, string> Maps cache key back to document ID */
    private array $keyToIdMap = [];

    public function __construct(
        ?CacheItemPoolInterface $externalCache = null,
        private readonly int $defaultTtl = 3600,
    ) {
        $localCache = new ArrayAdapter(
            defaultLifetime: $this->defaultTtl,
            storeSerialized: false,
        );

        if ($externalCache !== null) {
            $chainedCache = new ChainAdapter([
                $localCache,
                $externalCache,
            ]);
            $this->cache = new TagAwareAdapter($chainedCache);
        } else {
            $this->cache = new TagAwareAdapter($localCache);
        }
    }

    public function get(string $database, string $id): ?array
    {
        $key = $this->getCacheKey($database, $id);
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            return null;
        }

        $value = $item->get();

        return is_array($value) ? $value : null;
    }

    public function set(string $database, string $id, array $data, ?int $ttl = null): void
    {
        $key = $this->getCacheKey($database, $id);
        $item = $this->cache->getItem($key);

        $item->set($data);
        $item->expiresAfter($ttl ?? $this->defaultTtl);
        $item->tag([
            $this->getDatabaseTag($database),
            $this->getDocumentTag($database, $id),
        ]);

        $this->cache->save($item);
        $this->keyToIdMap[$key] = $id;
    }

    public function delete(string $database, string $id): void
    {
        $this->cache->invalidateTags([$this->getDocumentTag($database, $id)]);

        $key = $this->getCacheKey($database, $id);
        unset($this->keyToIdMap[$key]);
    }

    public function invalidateDatabase(string $database): void
    {
        $this->cache->invalidateTags([$this->getDatabaseTag($database)]);
    }

    public function has(string $database, string $id): bool
    {
        return $this->cache->hasItem($this->getCacheKey($database, $id));
    }

    public function getMultiple(string $database, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $keyToId = [];
        foreach ($ids as $id) {
            $key = $this->getCacheKey($database, $id);
            $keyToId[$key] = $id;
        }

        $results = [];
        foreach ($this->cache->getItems(array_keys($keyToId)) as $key => $item) {
            if ($item->isHit()) {
                $value = $item->get();
                if (is_array($value)) {
                    $id = $keyToId[$key];
                    $results[$id] = $value;
                }
            }
        }

        return $results;
    }

    public function clear(): void
    {
        $this->cache->clear();
        $this->keyToIdMap = [];
    }

    private function getCacheKey(string $database, string $id): string
    {
        // Use a hash to ensure valid cache key characters
        return sprintf('doc.%s.%s', $this->sanitizeKey($database), hash('xxh128', $id));
    }

    private function getDatabaseTag(string $database): string
    {
        return sprintf('db.%s', $this->sanitizeKey($database));
    }

    private function getDocumentTag(string $database, string $id): string
    {
        return sprintf('doc.%s.%s', $this->sanitizeKey($database), hash('xxh128', $id));
    }

    /**
     * Sanitize a string to be a valid cache key component.
     */
    private function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_.-]/', '_', $key) ?? $key;
    }
}
