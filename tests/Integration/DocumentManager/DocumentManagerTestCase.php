<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Integration\DocumentManager;

use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\DocumentManager;
use SmrtSystems\Couch\Tests\Integration\Helper\IntegrationTestHelper;

abstract class DocumentManagerTestCase extends TestCase {
    use IntegrationTestHelper;

    protected DocumentManager $manager;

    /**
     * The documents that are created during the test.
     *
     * @var array<int, object>
     */
    protected array $documents = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->createDocumentManager();
    }

    protected function persistAndTrack(object $document): void
    {
        $this->manager->persist($document);
        $this->documents[] = $document;
    }

    protected function tearDown(): void
    {
        foreach ($this->documents as $document) {
            $this->manager->remove($document);
        }

        $this->manager->flush();
    }
}