<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Integration\DocumentManager;

use PHPUnit\Framework\Attributes\CoversMethod;
use SmrtSystems\Couch\DocumentManager;
use SmrtSystems\Couch\Tests\Fixtures\User;
use SmrtSystems\Couch\Tests\Fixtures\UserWithoutRequiredId;

#[CoversMethod(DocumentManager::class, 'get')]
class DocumentManagerGetTest extends DocumentManagerTestCase
{
    public function testIdSetOnFlush(): void {
        $user = new UserWithoutRequiredId(
            name: 'Thomas A. Anderson',
        );

        $this->persistAndTrack($user);
        $this->manager->flush();

        $this->assertNotNull($user->id);
    }

    public function testFind(): void {
        $user = new UserWithoutRequiredId(name: 'Morpheus');
        $this->persistAndTrack($user);
        $this->manager->flush();

        $foundUser = $this->manager->get(UserWithoutRequiredId::class, $user->id);

        $this->assertNotNull($foundUser);
        $this->assertSame($user->id, $foundUser->id);
        $this->assertSame('Morpheus', $foundUser->name);
    }

    public function testFindReturnsNullWhenNotFound(): void {
        $foundUser = $this->manager->get(User::class, 'non-existing-id');

        $this->assertNull($foundUser);
    }
}
