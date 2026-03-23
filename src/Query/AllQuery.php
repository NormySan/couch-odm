<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Query;

/**
 * Fluent builder for CouchDB _all_docs range queries.
 *
 * This builder only supports a subset of options provided by the _all_docs
 * endpoint for the responses to be compatible with the ODM implementation.
 */
final class AllQuery
{
    public const string HIGH_UNICODE_CHARACTER = "\ufff0";

    private ?string $startKey = null;
    private ?string $endKey = null;
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
            'include_docs' => true,
        ];

        if ($this->startKey !== null) {
            $options['startkey'] = $this->startKey;
        }

        if ($this->endKey !== null) {
            $options['endkey'] = $this->endKey;
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
