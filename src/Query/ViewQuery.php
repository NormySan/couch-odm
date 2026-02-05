<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Query;

/**
 * Fluent builder for CouchDB view queries.
 */
final class ViewQuery
{
    private mixed $key = null;

    /** @var array<mixed>|null */
    private ?array $keys = null;

    private mixed $startKey = null;
    private mixed $endKey = null;
    private ?string $startKeyDocId = null;
    private ?string $endKeyDocId = null;
    private bool $includeDocs = false;
    private ?int $limit = null;
    private ?int $skip = null;
    private bool $descending = false;
    private ?bool $reduce = null;
    private bool $group = false;
    private ?int $groupLevel = null;
    private bool $inclusiveEnd = true;
    private ?bool $stable = null;
    private ?string $stale = null;

    public function __construct(
        private readonly string $designDoc,
        private readonly string $viewName,
    ) {}

    public static function create(string $designDoc, string $viewName): self
    {
        return new self($designDoc, $viewName);
    }

    /**
     * Query for a specific key.
     */
    public function key(mixed $key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Query for multiple specific keys.
     *
     * @param array<mixed> $keys
     */
    public function keys(array $keys): self
    {
        $this->keys = $keys;

        return $this;
    }

    /**
     * Set the start key for a range query.
     */
    public function startKey(mixed $key): self
    {
        $this->startKey = $key;

        return $this;
    }

    /**
     * Set the end key for a range query.
     */
    public function endKey(mixed $key): self
    {
        $this->endKey = $key;

        return $this;
    }

    /**
     * Set both start and end keys.
     */
    public function range(mixed $startKey, mixed $endKey): self
    {
        $this->startKey = $startKey;
        $this->endKey = $endKey;

        return $this;
    }

    /**
     * Set the start key document ID (for pagination within same key).
     */
    public function startKeyDocId(string $docId): self
    {
        $this->startKeyDocId = $docId;

        return $this;
    }

    /**
     * Set the end key document ID (for pagination within same key).
     */
    public function endKeyDocId(string $docId): self
    {
        $this->endKeyDocId = $docId;

        return $this;
    }

    /**
     * Include full documents in the response.
     */
    public function includeDocs(bool $include = true): self
    {
        $this->includeDocs = $include;

        return $this;
    }

    /**
     * Set the maximum number of results to return.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Set the number of results to skip.
     */
    public function skip(int $skip): self
    {
        $this->skip = $skip;

        return $this;
    }

    /**
     * Return results in descending order.
     */
    public function descending(bool $descending = true): self
    {
        $this->descending = $descending;

        return $this;
    }

    /**
     * Enable or disable the reduce function.
     */
    public function reduce(bool $reduce = true): self
    {
        $this->reduce = $reduce;

        return $this;
    }

    /**
     * Group results by key.
     */
    public function group(bool $group = true): self
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Set the group level for array keys.
     */
    public function groupLevel(int $level): self
    {
        $this->groupLevel = $level;

        return $this;
    }

    /**
     * Include the end key in the results.
     */
    public function inclusiveEnd(bool $inclusive = true): self
    {
        $this->inclusiveEnd = $inclusive;

        return $this;
    }

    /**
     * Use stable results (sorted by document ID).
     */
    public function stable(bool $stable = true): self
    {
        $this->stable = $stable;

        return $this;
    }

    /**
     * Allow stale results for better performance.
     *
     * @param string $stale 'ok' or 'update_after'
     */
    public function stale(string $stale): self
    {
        $this->stale = $stale;

        return $this;
    }

    /**
     * Get the design document name.
     */
    public function getDesignDoc(): string
    {
        return $this->designDoc;
    }

    /**
     * Get the view name.
     */
    public function getViewName(): string
    {
        return $this->viewName;
    }

    /**
     * Get the query options.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        $options = [];

        if ($this->key !== null) {
            $options['key'] = $this->key;
        }

        if ($this->keys !== null) {
            $options['keys'] = $this->keys;
        }

        if ($this->startKey !== null) {
            $options['startkey'] = $this->startKey;
        }

        if ($this->endKey !== null) {
            $options['endkey'] = $this->endKey;
        }

        if ($this->startKeyDocId !== null) {
            $options['startkey_docid'] = $this->startKeyDocId;
        }

        if ($this->endKeyDocId !== null) {
            $options['endkey_docid'] = $this->endKeyDocId;
        }

        if ($this->includeDocs) {
            $options['include_docs'] = true;
        }

        if ($this->limit !== null) {
            $options['limit'] = $this->limit;
        }

        if ($this->skip !== null) {
            $options['skip'] = $this->skip;
        }

        if ($this->descending) {
            $options['descending'] = true;
        }

        if ($this->reduce !== null) {
            $options['reduce'] = $this->reduce;
        }

        if ($this->group) {
            $options['group'] = true;
        }

        if ($this->groupLevel !== null) {
            $options['group_level'] = $this->groupLevel;
        }

        if (!$this->inclusiveEnd) {
            $options['inclusive_end'] = false;
        }

        if ($this->stable !== null) {
            $options['stable'] = $this->stable;
        }

        if ($this->stale !== null) {
            $options['stale'] = $this->stale;
        }

        return $options;
    }
}
