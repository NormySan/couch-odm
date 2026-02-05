<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Exception;

/**
 * Thrown when a document update conflicts with the current revision.
 */
final class ConflictException extends CouchException
{
    public function __construct(
        public readonly string $database,
        public readonly string $documentId,
        public readonly ?string $revision,
    ) {
        parent::__construct(
            sprintf(
                "Conflict saving document '%s' with revision '%s' in database '%s'",
                $documentId,
                $revision ?? 'null',
                $database
            )
        );
    }
}
