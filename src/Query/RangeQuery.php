<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Query;

/**
 * Fluent builder for CouchDB _all_docs range queries.
 */
final class RangeQuery
{
    private ?string $startKey = null;
    private ?string $endKey = null;
    /** @todo This should not be configurable, we should always include docs since we need to hydrate. */
    private bool $includeDocs = true;
    private ?int $limit = null;
    private ?int $skip = null;
    private bool $descending = false;
    private bool $inclusiveEnd = true;

    /** @var string[]|null */
    private ?array $keys = null;

    public static function create(): self
    {
        return new self();
    }

    /**
     * Set the start key for the range.
     */
    public function startKey(string $key): self
    {
        $this->startKey = $key;

        return $this;
    }

    /**
     * Set the end key for the range.
     */
    public function endKey(string $key): self
    {
        $this->endKey = $key;

        return $this;
    }

    /**
     * Set both start and end keys.
     */
    public function range(string $startKey, string $endKey): self
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
     * Query specific document IDs.
     *
     * @param string[] $keys
     */
    public function keys(array $keys): self
    {
        $this->keys = $keys;

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
     * Include the end key in the results.
     */
    public function inclusiveEnd(bool $inclusive = true): self
    {
        $this->inclusiveEnd = $inclusive;

        return $this;
    }

    /**
     * Get the query options.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        $options = [
            'include_docs' => $this->includeDocs,
        ];

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

        if ($this->keys !== null) {
            $options['keys'] = $this->keys;
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

        if (!$this->inclusiveEnd) {
            $options['inclusive_end'] = false;
        }

        return $options;
    }
}
