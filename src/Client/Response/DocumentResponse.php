<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Client\Response;

/**
 * Represents a response containing a single CouchDB document.
 */
final class DocumentResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    public function getId(): ?string
    {
        return $this->data['_id'] ?? null;
    }

    public function getRevision(): ?string
    {
        return $this->data['_rev'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
}
