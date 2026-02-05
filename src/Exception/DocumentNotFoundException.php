<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Exception;

/**
 * Thrown when a document cannot be found in the database.
 */
final class DocumentNotFoundException extends CouchException
{
    public function __construct(
        public readonly string $database,
        public readonly string $documentId,
    ) {
        parent::__construct(
            sprintf("Document '%s' not found in database '%s'", $documentId, $database)
        );
    }
}
