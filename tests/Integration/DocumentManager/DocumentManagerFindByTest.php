<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Integration\DocumentManager;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use SmrtSystems\Couch\DocumentManager;
use SmrtSystems\Couch\Query\FindQuery;
use SmrtSystems\Couch\Tests\Fixtures\Character;

#[CoversMethod(DocumentManager::class, 'findBy')]
class DocumentManagerFindByTest extends DocumentManagerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createCharacters();
    }


    #[DataProvider('findByDataProvider')]
    public function testFindBy(FindQuery $query, array $expectedIds): void {
        $result = $this->manager->findBy(Character::class, $query);
        $users = iterator_to_array($result);

        $this->assertCount(count($expectedIds), $users);
        $this->assertEqualsCanonicalizing($expectedIds, array_map(fn($user) => $user->id, $users));
    }

    public static function findByDataProvider(): array
    {
        return [
            'Captains' => [
                'query' => FindQuery::create()
                    ->where('role', 'captain'),
                'expectedIds' => ['morpheus', 'niobe'],
            ],
            'Captains and crew' => [
                'query' => FindQuery::create()
                    ->where('role','$in', ['captain', 'crew']),
                'expectedIds' => ['apoc', 'morpheus', 'niobe', 'tank'],
            ],
            'Captains born after 1970' => [
                'query' => FindQuery::create()
                    ->where('role','$eq', 'captain')
                    ->andWhere('birthday', '$gte', '1970-01-01'),
                'expectedIds' => ['niobe'],
            ],
            'Crew between 1960 and 1970' => [
                'query' => FindQuery::create()
                    ->where('role','$eq', 'crew')
                    ->andWhere('birthday', '$gte', '1960-01-01')
                    ->andWhere('birthday', '$lte', '1970-12-31'),
                'expectedIds' => ['tank'],
            ],
        ];
    }

    private function createCharacters(): void {
        $this->persistAndTrack(new Character(
            id: 'apoc',
            name: 'Apoc',
            role: 'crew',
            birthday: new DateTimeImmutable('1972-12-18'),
        ));

        $this->persistAndTrack(new Character(
            id: 'morpheus',
            name: 'Morpheus',
            role: 'captain',
            birthday: new DateTimeImmutable('1961-07-30'),
        ));

        $this->persistAndTrack(new Character(
            id: 'neo',
            name: 'Neo',
            role: 'the-one',
            birthday: new DateTimeImmutable('1964-09-02'),
        ));

        $this->persistAndTrack(new Character(
            id: 'niobe',
            name: 'Niobe',
            role: 'captain',
            birthday: new DateTimeImmutable('1971-09-18'),
        ));

        $this->persistAndTrack(new Character(
            id: 'tank',
            name: 'Tank',
            role: 'crew',
            birthday: new DateTimeImmutable('1967-07-08'),
        ));

        $this->persistAndTrack(new Character(
            id: 'smith',
            name: 'Agent Smith',
            role: 'agent',
            birthday: new DateTimeImmutable('1960-04-04'),
        ));

        $this->manager->flush();
    }
}