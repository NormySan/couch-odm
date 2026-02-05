<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Query;

/**
 * Fluent builder for CouchDB Mango queries (_find endpoint).
 */
final class FindQuery
{
    /** @var array<string, mixed> */
    private array $selector = [];

    private ?int $limit = null;
    private ?int $skip = null;

    /** @var array<array<string, string>> */
    private array $sort = [];

    /** @var string[] */
    private array $fields = [];

    private ?string $bookmark = null;
    private ?string $useIndex = null;
    private bool $executionStats = false;

    public static function create(): self
    {
        return new self();
    }

    /**
     * Add a where condition.
     *
     * Examples:
     *   ->where('status', 'active')           // status = 'active'
     *   ->where('age', '$gte', 18)            // age >= 18
     *   ->where('tags', '$in', ['a', 'b'])    // tags in ['a', 'b']
     */
    public function where(string $field, mixed $operatorOrValue, mixed $value = null): self
    {
        if ($value === null && !$this->isOperator($operatorOrValue)) {
            // Simple equality: where('status', 'active')
            $this->selector[$field] = ['$eq' => $operatorOrValue];
        } else {
            // Operator form: where('age', '$gte', 18)
            $this->selector[$field] = [$operatorOrValue => $value];
        }

        return $this;
    }

    /**
     * Alias for where().
     */
    public function andWhere(string $field, mixed $operatorOrValue, mixed $value = null): self
    {
        return $this->where($field, $operatorOrValue, $value);
    }

    /**
     * Add an OR condition group.
     *
     * @param array<array<string, mixed>> $conditions
     */
    public function orWhere(array $conditions): self
    {
        $this->selector['$or'] = $conditions;

        return $this;
    }

    /**
     * Add a raw selector condition.
     *
     * @param array<string, mixed> $selector
     */
    public function whereRaw(array $selector): self
    {
        $this->selector = array_merge($this->selector, $selector);

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
     * Add a sort condition.
     */
    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $this->sort[] = [$field => strtolower($direction)];

        return $this;
    }

    /**
     * Add multiple sort conditions.
     *
     * @param array<string, string> $sorts field => direction
     */
    public function orderByMultiple(array $sorts): self
    {
        foreach ($sorts as $field => $direction) {
            $this->sort[] = [$field => strtolower($direction)];
        }

        return $this;
    }

    /**
     * Specify which fields to return.
     *
     * @param string[] $fields
     */
    public function select(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Set the bookmark for pagination.
     */
    public function bookmark(string $bookmark): self
    {
        $this->bookmark = $bookmark;

        return $this;
    }

    /**
     * Specify which index to use.
     *
     * @param string|array<string> $index Index name or [design_doc, index_name]
     */
    public function useIndex(string|array $index): self
    {
        $this->useIndex = is_array($index) ? implode('/', $index) : $index;

        return $this;
    }

    /**
     * Include execution statistics in the response.
     */
    public function withExecutionStats(bool $enabled = true): self
    {
        $this->executionStats = $enabled;

        return $this;
    }

    /**
     * Get the selector array.
     *
     * @return array<string, mixed>
     */
    public function getSelector(): array
    {
        return $this->selector;
    }

    /**
     * Get the query options.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        $options = [];

        if ($this->limit !== null) {
            $options['limit'] = $this->limit;
        }

        if ($this->skip !== null) {
            $options['skip'] = $this->skip;
        }

        if ($this->sort !== []) {
            $options['sort'] = $this->sort;
        }

        if ($this->fields !== []) {
            $options['fields'] = $this->fields;
        }

        if ($this->bookmark !== null) {
            $options['bookmark'] = $this->bookmark;
        }

        if ($this->useIndex !== null) {
            $options['use_index'] = $this->useIndex;
        }

        if ($this->executionStats) {
            $options['execution_stats'] = true;
        }

        return $options;
    }

    private function isOperator(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return in_array($value, [
            '$eq', '$ne', '$gt', '$gte', '$lt', '$lte',
            '$in', '$nin', '$exists', '$type', '$mod',
            '$regex', '$or', '$and', '$not', '$nor',
            '$all', '$elemMatch', '$size',
        ], true);
    }
}
