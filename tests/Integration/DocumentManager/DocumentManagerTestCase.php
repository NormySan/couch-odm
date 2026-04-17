<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Integration\DocumentManager;

use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\Client\CouchDbClientInterface;
use SmrtSystems\Couch\Client\Data\CreateDesignDocumentInput;
use SmrtSystems\Couch\DocumentManagerInterface;
use SmrtSystems\Couch\Hydration\DocumentMapperInterface;
use SmrtSystems\Couch\Tests\Integration\Helper\IntegrationTestHelper;

abstract class DocumentManagerTestCase extends TestCase {
    use IntegrationTestHelper;

    protected CouchDbClientInterface $client;
    protected DocumentManagerInterface $manager;
    protected DocumentMapperInterface $mapper;

    /**
     * The documents that are created during the test.
     *
     * @var array<int, object>
     */
    protected array $documents = [];

    /**
     * The design documents that are created during the test.
     *
     * @var array<int, array{ database: string, name: string }>
     */
    protected array $designDocuments = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createClient();
        $this->mapper = $this->createDocumentMapper();

        $this->manager = $this->createDocumentManager(
            $this->client,
            $this->mapper,
        );
    }

    /**
     * @param list<object> $documents
     */
    protected function persistFlushAndTrack(array $documents): void
    {
        foreach ($documents as $document) {
            $this->manager->persist($document);
            $this->documents[] = $document;
        }

        $this->manager->flush();
    }

    /**
     * @param class-string $className
     * @param array<string, mixed> $views
     */
    protected function createDesignDocument(string $className, string $name, array $views, string $language = 'javascript'): void
    {
        $database = $this->mapper->getDatabase($className);

        $this->client->createDesignDocument(
            input: new CreateDesignDocumentInput(
                database: $database,
                name: $name,
                views: $views,
            ),
        );

        $this->designDocuments[] = [
            'database' => $database,
            'name' => $name,
        ];
    }

    protected function tearDown(): void
    {
        foreach ($this->documents as $document) {
            $this->manager->remove($document);
        }

        $this->manager->flush();

        foreach ($this->designDocuments as $designDocument) {
            $this->client->deleteDesignDocument($designDocument['database'], $designDocument['name']);
        }
    }
}