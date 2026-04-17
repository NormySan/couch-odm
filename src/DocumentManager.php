<?php

declare(strict_types=1);

namespace SmrtSystems\Couch;

use InvalidArgumentException;
use SmrtSystems\Couch\Cache\DocumentCacheInterface;
use SmrtSystems\Couch\Client\CouchDbClientInterface;
use SmrtSystems\Couch\Exception\DocumentNotFoundException;
use SmrtSystems\Couch\Hydration\DocumentMapperInterface;
use SmrtSystems\Couch\Query\FindQuery;
use SmrtSystems\Couch\Query\AllQuery;
use SmrtSystems\Couch\Query\ViewQuery;
use SplObjectStorage;
use WeakMap;

/**
 * Main entry point for working with CouchDB documents.
 */
final class DocumentManager implements DocumentManagerInterface
{
    /**
     * Contains documents that are pending insertion e.g. create or update.
     *
     * @var SplObjectStorage<object, true>
     */
    private SplObjectStorage $pendingInserts;

    /**
     * Contains documents that are pending removal.
     *
     * @var SplObjectStorage<object, true>
     */
    private SplObjectStorage $pendingRemovals;

    /**
     * Keeps track of the revision of each loaded document.
     *
     * This removes the need for a revision property on the document classes
     * making them more lightweight. The revision is very much a CouchDB
     * concept and this doesn't need to leak into the documents.
     *
     * @var WeakMap<object, ?string>
     */
    private WeakMap $revisions;

    public function __construct(
        private readonly CouchDbClientInterface $client,
        private readonly DocumentMapperInterface $mapper,
        private readonly ?DocumentCacheInterface $cache = null,
    ) {
        $this->pendingInserts = new SplObjectStorage();
        $this->pendingRemovals = new SplObjectStorage();
        $this->revisions = new WeakMap();
    }

    public function get(string $className, string $id): ?object {
        $database = $this->mapper->getDatabase($className);

        $cachedDocument = $this->cache?->get($database, $id);
        if ($cachedDocument !== null) {
            $document = $this->mapper->toDocument($className, $cachedDocument);
            $this->revisions[$document] = $cachedDocument['_rev'] ?? null;

            return $document;
        }

        try {
            $data = $this->client->get($database, $id)->getData();

            return $this->hydrateAndCache($className, $database, $data);
        } catch (DocumentNotFoundException) {
            return null;
        }
    }

    public function findBy(string $className, FindQuery $query): iterable {
        $database = $this->mapper->getDatabase($className);

        $results = $this->client->find($database, $query->getSelector(), $query->getOptions());

        foreach ($results as $data) {
            $document = $this->hydrateAndCache($className, $database, $data);

            if ($document !== null) {
                yield $document;
            }
        }
    }

    public function all(string $className, AllQuery $query): iterable {
        $database = $this->mapper->getDatabase($className);

        foreach ($this->client->allDocs($database, $query->getOptions()) as $data) {
            $document = $this->hydrateAndCache($className, $database, $data);

            if ($document !== null) {
                yield $document;
            }
        }
    }

    public function view(string $className, ViewQuery $query): iterable {
        $database = $this->mapper->getDatabase($className);

        $rows = $this->client->view($database, $query->getName(), $query->getView(), $query->getOptions());

        foreach ($rows as $row) {
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

    public function persist(object $document): void {
        $this->pendingRemovals->detach($document);
        $this->pendingInserts->attach($document);
    }

    public function remove(object $document): void {
        $this->pendingInserts->detach($document);
        $this->pendingRemovals->attach($document);
    }

    public function flush(): void {
        foreach ($this->pendingInserts as $document) {
            $this->doSave($document);
        }

        foreach ($this->pendingRemovals as $document) {
            $this->doRemove($document);
        }

        $this->pendingInserts = new SplObjectStorage();
        $this->pendingRemovals = new SplObjectStorage();
    }

    public function clear(): void {
        $this->pendingInserts = new SplObjectStorage();
        $this->pendingRemovals = new SplObjectStorage();
        $this->revisions = new WeakMap();
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
        $this->revisions[$refreshed] = $freshData['_rev'] ?? null;

        return $refreshed;
    }

    public function detach(object $document): void {
        $this->pendingInserts->detach($document);
        $this->pendingRemovals->detach($document);
        unset($this->revisions[$document]);
    }

    /**
     * @todo I don't like the name of this method. Lets think of something better.
     */
    private function doSave(object $document): void {
        $data = $this->mapper->toArray($document);

        if (isset($data['_id']) === false) {
            $id = $this->generateId();
            $this->mapper->setId($document, $id);
            $data['_id'] = $id;
        }

        // Inject revision from internal tracking for updates
        $rev = $this->revisions[$document] ?? null;
        if ($rev !== null) {
            $data['_rev'] = $rev;
        }

        $database = $this->mapper->getDatabase($document::class);
        $response = $this->client->put($database, $data['_id'], $data);
        $savedData = $response->getData();

        $this->revisions[$document] = $response->getRevision();

        $this->cache?->set($database, $data['_id'], $savedData);
    }

    /**
     * @todo I don't like the name of this method. Lets think of something better.
     */
    private function doRemove(object $document): void
    {
        $className = $document::class;
        $database = $this->mapper->getDatabase($className);
        $id = $this->mapper->getId($document);
        $rev = $this->revisions[$document] ?? null;

        if ($id === null || $rev === null) {
            throw new InvalidArgumentException('Cannot remove a document without ID and revision');
        }

        $this->client->delete($database, $id, $rev);

        // Invalidate cache
        $this->cache?->delete($database, $id);
        unset($this->revisions[$document]);
    }



    /**
     *
     * @template TDocument of object
     *
     * @param class-string<TDocument> $className
     * @param array<string, mixed> $data
     *
     * @return TDocument|null
     *
     * @todo Throw if the document does not contain an ID?
     */
    private function hydrateAndCache(string $className, string $database, array $data): ?object {
        $id = $data['_id'] ?? null;

        if ($id === null) {
            return null;
        }

        $this->cache?->set($database, $id, $data);

        $document = $this->mapper->toDocument($className, $data);
        $this->revisions[$document] = $data['_rev'] ?? null;

        return $document;
    }

    /**
     * @todo The document manager should not generate IDs.
     */
    private function generateId(): string {
        // UUID v4 generation
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

}
