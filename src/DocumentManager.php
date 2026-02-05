<?php

declare(strict_types=1);

namespace SmrtSystems\Couch;

use InvalidArgumentException;
use SmrtSystems\Couch\Cache\DocumentCacheInterface;
use SmrtSystems\Couch\Client\CouchDbClientInterface;
use SmrtSystems\Couch\Exception\DocumentNotFoundException;
use SmrtSystems\Couch\Hydration\DocumentMapperInterface;
use SmrtSystems\Couch\Query\FindQuery;
use SmrtSystems\Couch\Query\RangeQuery;
use SmrtSystems\Couch\Query\ViewQuery;
use SplObjectStorage;

/**
 * Main entry point for working with CouchDB documents.
 */
final class DocumentManager implements DocumentManagerInterface
{
    /** @var SplObjectStorage<object, true> */
    private SplObjectStorage $pendingInserts;

    /** @var SplObjectStorage<object, true> */
    private SplObjectStorage $pendingRemovals;

    /** @var array<string, array<string, object>> class => [id => object] */
    private array $identityMap = [];

    public function __construct(
        private readonly CouchDbClientInterface $client,
        private readonly DocumentMapperInterface $mapper,
        private readonly ?DocumentCacheInterface $cache = null,
    ) {
        $this->pendingInserts = new SplObjectStorage();
        $this->pendingRemovals = new SplObjectStorage();
    }

    public function find(string $className, string $id): ?object
    {
        // Check identity map first
        $existing = $this->getFromIdentityMap($className, $id);
        if ($existing !== null) {
            return $existing;
        }

        $database = $this->mapper->getDatabase($className);

        // Check cache
        if ($this->cache !== null) {
            $cachedData = $this->cache->get($database, $id);
            if ($cachedData !== null) {
                $document = $this->mapper->toDocument($className, $cachedData);
                $this->addToIdentityMap($className, $id, $document);

                return $document;
            }
        }

        // Fetch from database
        try {
            $response = $this->client->get($database, $id);
            $data = $response->getData();
        } catch (DocumentNotFoundException) {
            return null;
        }

        // Cache the result
        $this->cache?->set($database, $id, $data);

        // Hydrate and add to identity map
        $document = $this->mapper->toDocument($className, $data);
        $this->addToIdentityMap($className, $id, $document);

        return $document;
    }

    public function findBy(string $className, FindQuery $query): iterable
    {
        $database = $this->mapper->getDatabase($className);

        foreach ($this->client->find($database, $query->getSelector(), $query->getOptions()) as $data) {
            $document = $this->hydrateAndCache($className, $database, $data);
            if ($document !== null) {
                yield $document;
            }
        }
    }

    public function findByRange(string $className, RangeQuery $query): iterable
    {
        $database = $this->mapper->getDatabase($className);

        foreach ($this->client->allDocs($database, $query->getOptions()) as $data) {
            $document = $this->hydrateAndCache($className, $database, $data);
            if ($document !== null) {
                yield $document;
            }
        }
    }

    public function findByView(string $className, ViewQuery $query): iterable
    {
        $database = $this->mapper->getDatabase($className);

        foreach ($this->client->view($database, $query->getDesignDoc(), $query->getViewName(), $query->getOptions()) as $row) {
            // Views return rows with 'doc' when include_docs is true
            $data = $row['doc'] ?? $row['value'] ?? null;

            if (!is_array($data)) {
                continue;
            }

            $document = $this->hydrateAndCache($className, $database, $data);
            if ($document !== null) {
                yield $document;
            }
        }
    }

    public function persist(object $document): void
    {
        $this->pendingRemovals->detach($document);
        $this->pendingInserts->attach($document);
    }

    public function remove(object $document): void
    {
        $this->pendingInserts->detach($document);
        $this->pendingRemovals->attach($document);
    }

    public function flush(): void
    {
        // Process inserts/updates
        foreach ($this->pendingInserts as $document) {
            $this->doSave($document);
        }

        // Process removals
        foreach ($this->pendingRemovals as $document) {
            $this->doRemove($document);
        }

        $this->pendingInserts = new SplObjectStorage();
        $this->pendingRemovals = new SplObjectStorage();
    }

    public function clear(): void
    {
        $this->pendingInserts = new SplObjectStorage();
        $this->pendingRemovals = new SplObjectStorage();
        $this->identityMap = [];
    }

    public function refresh(object $document): object
    {
        $className = $document::class;
        $id = $this->mapper->getId($document);

        if ($id === null) {
            throw new InvalidArgumentException('Cannot refresh a document without an ID');
        }

        $database = $this->mapper->getDatabase($className);
        $response = $this->client->get($database, $id);
        $freshData = $response->getData();

        // Update cache
        $this->cache?->set($database, $id, $freshData);

        // Re-hydrate the document
        $refreshed = $this->mapper->toDocument($className, $freshData);
        $this->addToIdentityMap($className, $id, $refreshed);

        return $refreshed;
    }

    public function contains(object $document): bool
    {
        $className = $document::class;
        $id = $this->mapper->getId($document);

        if ($id === null) {
            return false;
        }

        return $this->getFromIdentityMap($className, $id) === $document;
    }

    public function detach(object $document): void
    {
        $className = $document::class;
        $id = $this->mapper->getId($document);

        if ($id !== null) {
            $this->removeFromIdentityMap($className, $id);
        }

        $this->pendingInserts->detach($document);
        $this->pendingRemovals->detach($document);
    }

    /**
     * Get the underlying CouchDB client.
     */
    public function getClient(): CouchDbClientInterface
    {
        return $this->client;
    }

    /**
     * Get the document mapper.
     */
    public function getMapper(): DocumentMapperInterface
    {
        return $this->mapper;
    }

    private function doSave(object $document): void
    {
        $className = $document::class;
        $database = $this->mapper->getDatabase($className);
        $data = $this->mapper->toArray($document);

        $id = $data['_id'] ?? $this->generateId();

        // Ensure the ID is set in the data
        $data['_id'] = $id;

        $response = $this->client->put($database, $id, $data);
        $savedData = $response->getData();

        // Update the document with the new revision
        $updatedDocument = $this->mapper->toDocument($className, $savedData);

        // Invalidate and update cache
        $this->cache?->delete($database, $id);
        $this->cache?->set($database, $id, $savedData);

        // Update identity map with the document that has the new revision
        $this->addToIdentityMap($className, $id, $updatedDocument);
    }

    private function doRemove(object $document): void
    {
        $className = $document::class;
        $database = $this->mapper->getDatabase($className);
        $id = $this->mapper->getId($document);
        $rev = $this->mapper->getRevision($document);

        if ($id === null || $rev === null) {
            throw new InvalidArgumentException('Cannot remove a document without ID and revision');
        }

        $this->client->delete($database, $id, $rev);

        // Invalidate cache
        $this->cache?->delete($database, $id);

        // Remove from identity map
        $this->removeFromIdentityMap($className, $id);
    }

    /**
     * @param class-string $className
     * @param array<string, mixed> $data
     */
    private function hydrateAndCache(string $className, string $database, array $data): ?object
    {
        $id = $data['_id'] ?? null;

        if ($id === null) {
            return null;
        }

        // Check identity map
        $existing = $this->getFromIdentityMap($className, $id);
        if ($existing !== null) {
            return $existing;
        }

        $document = $this->mapper->toDocument($className, $data);

        $this->cache?->set($database, $id, $data);
        $this->addToIdentityMap($className, $id, $document);

        return $document;
    }

    private function generateId(): string
    {
        // UUID v4 generation
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * @param class-string $className
     */
    private function getFromIdentityMap(string $className, string $id): ?object
    {
        return $this->identityMap[$className][$id] ?? null;
    }

    /**
     * @param class-string $className
     */
    private function addToIdentityMap(string $className, string $id, object $document): void
    {
        $this->identityMap[$className][$id] = $document;
    }

    /**
     * @param class-string $className
     */
    private function removeFromIdentityMap(string $className, string $id): void
    {
        unset($this->identityMap[$className][$id]);
    }
}
