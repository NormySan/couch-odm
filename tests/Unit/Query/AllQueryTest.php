<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Unit\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\Query\AllQuery;

#[CoversClass(AllQuery::class)]
final class AllQueryTest extends TestCase
{
    #[DataProvider('createAllQueryDataProvider')]
    public function testCreateAllQuery(AllQuery $query, array $expectedOptions): void
    {
        $this->assertSame($expectedOptions, $query->getOptions());
    }

    public static function createAllQueryDataProvider(): array {
        return [
            'Default options' => [
                'query' => AllQuery::create(),
                'expectedOptions' => self::getDefaultOptions(),
            ],
            'Start key' => [
                'query' => AllQuery::create()->startKey('123'),
                'expectedOptions' => self::getDefaultOptions(['startkey' => '123']),
            ],
            'End key' => [
                'query' => AllQuery::create()->endKey('789'),
                'expectedOptions' => self::getDefaultOptions(['endkey' => '789']),
            ],
            'Range' => [
                'query' => AllQuery::create()->range('123', '789'),
                'expectedOptions' => self::getDefaultOptions(['startkey' => '123', 'endkey' => '789']),
            ],
            'Keys' => [
                'query' => AllQuery::create()->keys(['doc1', 'doc2', 'doc3']),
                'expectedOptions' => self::getDefaultOptions(['keys' => ['doc1', 'doc2', 'doc3']]),
            ],
            'Limit' => [
                'query' => AllQuery::create()->limit(10),
                'expectedOptions' => self::getDefaultOptions(['limit' => 10]),
            ],
            'Skip' => [
                'query' => AllQuery::create()->skip(5),
                'expectedOptions' => self::getDefaultOptions(['skip' => 5]),
            ],
            'Descending' => [
                'query' => AllQuery::create()->descending(),
                'expectedOptions' => self::getDefaultOptions(['descending' => true]),
            ],
            'Descending false' => [
                'query' => AllQuery::create()->descending(false),
                'expectedOptions' => self::getDefaultOptions(),
            ],
            'Inclusive end false' => [
                'query' => AllQuery::create()->inclusiveEnd(false),
                'expectedOptions' => self::getDefaultOptions(['inclusive_end' => false]),
            ],
            'Inclusive end true' => [
                'query' => AllQuery::create()->inclusiveEnd(true),
                'expectedOptions' => self::getDefaultOptions(),
            ],
            'All options' => [
                'query' => AllQuery::create()
                    ->startKey('123')
                    ->endKey('789')
                    ->keys(['doc1', 'doc2'])
                    ->limit(10)
                    ->skip(5)
                    ->descending()
                    ->inclusiveEnd(false),
                'expectedOptions' => [
                    'include_docs' => true,
                    'startkey' => '123',
                    'endkey' => '789',
                    'keys' => ['doc1', 'doc2'],
                    'limit' => 10,
                    'skip' => 5,
                    'descending' => true,
                    'inclusive_end' => false,
                ],
            ],
        ];
    }

    /**
     * Get the default options with additional options merged in.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private static function getDefaultOptions(array $options = []): array {
        return [
            'include_docs' => true,
            ...$options,
        ];
    }
}
