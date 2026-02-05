<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Exception;

/**
 * Thrown when there is an error communicating with CouchDB.
 */
final class CouchDbException extends CouchException
{
    /**
     * @param array<string, mixed>|null $responseBody
     */
    public function __construct(
        string $message,
        public readonly ?int $httpStatusCode = null,
        public readonly ?array $responseBody = null,
    ) {
        parent::__construct($message);
    }

    /**
     * @param array<string, mixed>|null $responseBody
     */
    public static function fromResponse(int $statusCode, ?array $responseBody = null): self
    {
        $error = $responseBody['error'] ?? 'unknown_error';
        $reason = $responseBody['reason'] ?? 'Unknown reason';

        return new self(
            sprintf("CouchDB error: %s - %s", $error, $reason),
            $statusCode,
            $responseBody
        );
    }
}
