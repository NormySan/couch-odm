<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Integration;

use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\DocumentManager;
use SmrtSystems\Couch\Tests\Document\User;
use SmrtSystems\Couch\Tests\Integration\Helper\IntegrationTestHelper;

class DocumentManagerTest extends TestCase {
    use IntegrationTestHelper;

    private DocumentManager $manager;

    protected function setUp(): void {
        parent::setUp();

        $this->manager = $this->createDocumentManager();
    }

    public function testIdAndRevisionSetInSave(): void {
        $user = new User(
            name: 'Thomas A. Anderson',
        );

        $this->manager->persist($user);
        $this->manager->flush();

        $this->assertNotNull($user->id);
        $this->assertNotNull($user->rev);
    }

}