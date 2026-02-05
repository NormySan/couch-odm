<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Unit\Query;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\Query\RangeQuery;

final class RangeQueryTest extends TestCase
{
    #[Test]
    public function it_creates_query_with_defaults(): void
    {
        $query = RangeQuery::create();
        $options = $query->getOptions();

        $this->assertTrue($options['include_docs']);
    }

    #[Test]
    public function it_sets_start_key(): void
    {
        $query = RangeQuery::create()
            ->startKey('user_a');

        $options = $query->getOptions();

        $this->assertSame('user_a', $options['startkey']);
    }

    #[Test]
    public function it_sets_end_key(): void
    {
        $query = RangeQuery::create()
            ->endKey('user_z');

        $options = $query->getOptions();

        $this->assertSame('user_z', $options['endkey']);
    }

    #[Test]
    public function it_sets_range(): void
    {
        $query = RangeQuery::create()
            ->range('user_a', 'user_z');

        $options = $query->getOptions();

        $this->assertSame('user_a', $options['startkey']);
        $this->assertSame('user_z', $options['endkey']);
    }

    #[Test]
    public function it_sets_keys(): void
    {
        $query = RangeQuery::create()
            ->keys(['user_1', 'user_2', 'user_3']);

        $options = $query->getOptions();

        $this->assertSame(['user_1', 'user_2', 'user_3'], $options['keys']);
    }

    #[Test]
    public function it_disables_include_docs(): void
    {
        $query = RangeQuery::create()
            ->includeDocs(false);

        $options = $query->getOptions();

        $this->assertFalse($options['include_docs']);
    }

    #[Test]
    public function it_sets_limit(): void
    {
        $query = RangeQuery::create()
            ->limit(100);

        $options = $query->getOptions();

        $this->assertSame(100, $options['limit']);
    }

    #[Test]
    public function it_sets_skip(): void
    {
        $query = RangeQuery::create()
            ->skip(50);

        $options = $query->getOptions();

        $this->assertSame(50, $options['skip']);
    }

    #[Test]
    public function it_sets_descending(): void
    {
        $query = RangeQuery::create()
            ->descending();

        $options = $query->getOptions();

        $this->assertTrue($options['descending']);
    }

    #[Test]
    public function it_sets_inclusive_end(): void
    {
        $query = RangeQuery::create()
            ->inclusiveEnd(false);

        $options = $query->getOptions();

        $this->assertFalse($options['inclusive_end']);
    }

    #[Test]
    public function it_builds_complex_range_query(): void
    {
        $query = RangeQuery::create()
            ->range('user_', "user_\ufff0")
            ->includeDocs()
            ->limit(50)
            ->descending();

        $options = $query->getOptions();

        $this->assertSame('user_', $options['startkey']);
        $this->assertSame("user_\ufff0", $options['endkey']);
        $this->assertTrue($options['include_docs']);
        $this->assertSame(50, $options['limit']);
        $this->assertTrue($options['descending']);
    }
}
