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
    private ?int $limit = null;
    private ?int $skip = null;
    private bool $descending = false;
    private bool $inclusiveEnd = true;
    private ?bool $stable = null;
    private ?string $stale = null;

    public function __construct(
        private readonly string $name,
        private readonly string $view,
    ) {}

    public static function create(string $doc, string $view): self
    {
        return new self($doc, $view);
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the view name.
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * Get the query options.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        $options = [
            'include_docs' => true,
        ];

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

        if ($this->stable !== null) {
            $options['stable'] = $this->stable;
        }

        if ($this->stale !== null) {
            $options['stale'] = $this->stale;
        }

        return $options;
    }
}
