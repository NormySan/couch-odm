<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Client\Response;

/**
 * Represents a response from a bulk document operation.
 */
final class BulkResponse
{
    /** @var array<int, array{id: string, rev?: string, ok?: bool, error?: string, reason?: string}> */
    private readonly array $results;

    /**
     * @param array<int, array<string, mixed>> $data
     */
    public function __construct(array $data)
    {
        $this->results = $data;
    }

    /**
     * @return array<int, array{id: string, rev?: string, ok?: bool, error?: string, reason?: string}>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @return array<int, array{id: string, rev: string}>
     */
    public function getSuccessful(): array
    {
        return array_filter(
            $this->results,
            static fn(array $result): bool => isset($result['ok']) && $result['ok'] === true
        );
    }

    /**
     * @return array<int, array{id: string, error: string, reason: string}>
     */
    public function getFailed(): array
    {
        return array_filter(
            $this->results,
            static fn(array $result): bool => isset($result['error'])
        );
    }

    public function hasErrors(): bool
    {
        return count($this->getFailed()) > 0;
    }

    public function isFullySuccessful(): bool
    {
        return !$this->hasErrors();
    }

    public function getCount(): int
    {
        return count($this->results);
    }

    public function getSuccessCount(): int
    {
        return count($this->getSuccessful());
    }

    public function getErrorCount(): int
    {
        return count($this->getFailed());
    }
}
