<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Unit\Query;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\Query\ViewQuery;

final class ViewQueryTest extends TestCase
{
    #[Test]
    public function it_creates_query_with_design_doc_and_view(): void
    {
        $query = ViewQuery::create('app', 'by_status');

        $this->assertSame('app', $query->getDesignDoc());
        $this->assertSame('by_status', $query->getViewName());
    }

    #[Test]
    public function it_sets_single_key(): void
    {
        $query = ViewQuery::create('app', 'by_status')
            ->key('active');

        $options = $query->getOptions();

        $this->assertSame('active', $options['key']);
    }

    #[Test]
    public function it_sets_multiple_keys(): void
    {
        $query = ViewQuery::create('app', 'by_status')
            ->keys(['active', 'pending']);

        $options = $query->getOptions();

        $this->assertSame(['active', 'pending'], $options['keys']);
    }

    #[Test]
    public function it_sets_key_range(): void
    {
        $query = ViewQuery::create('app', 'by_date')
            ->startKey('2024-01-01')
            ->endKey('2024-12-31');

        $options = $query->getOptions();

        $this->assertSame('2024-01-01', $options['startkey']);
        $this->assertSame('2024-12-31', $options['endkey']);
    }

    #[Test]
    public function it_sets_range_shorthand(): void
    {
        $query = ViewQuery::create('app', 'by_date')
            ->range('2024-01-01', '2024-12-31');

        $options = $query->getOptions();

        $this->assertSame('2024-01-01', $options['startkey']);
        $this->assertSame('2024-12-31', $options['endkey']);
    }

    #[Test]
    public function it_sets_include_docs(): void
    {
        $query = ViewQuery::create('app', 'by_status')
            ->includeDocs();

        $options = $query->getOptions();

        $this->assertTrue($options['include_docs']);
    }

    #[Test]
    public function it_sets_limit_and_skip(): void
    {
        $query = ViewQuery::create('app', 'by_status')
            ->limit(100)
            ->skip(50);

        $options = $query->getOptions();

        $this->assertSame(100, $options['limit']);
        $this->assertSame(50, $options['skip']);
    }

    #[Test]
    public function it_sets_descending(): void
    {
        $query = ViewQuery::create('app', 'by_date')
            ->descending();

        $options = $query->getOptions();

        $this->assertTrue($options['descending']);
    }

    #[Test]
    public function it_sets_reduce(): void
    {
        $query = ViewQuery::create('app', 'count_by_status')
            ->reduce();

        $options = $query->getOptions();

        $this->assertTrue($options['reduce']);
    }

    #[Test]
    public function it_disables_reduce(): void
    {
        $query = ViewQuery::create('app', 'count_by_status')
            ->reduce(false);

        $options = $query->getOptions();

        $this->assertFalse($options['reduce']);
    }

    #[Test]
    public function it_sets_group(): void
    {
        $query = ViewQuery::create('app', 'count_by_status')
            ->reduce()
            ->group();

        $options = $query->getOptions();

        $this->assertTrue($options['group']);
    }

    #[Test]
    public function it_sets_group_level(): void
    {
        $query = ViewQuery::create('app', 'by_date_and_status')
            ->reduce()
            ->groupLevel(2);

        $options = $query->getOptions();

        $this->assertSame(2, $options['group_level']);
    }

    #[Test]
    public function it_sets_inclusive_end(): void
    {
        $query = ViewQuery::create('app', 'by_status')
            ->inclusiveEnd(false);

        $options = $query->getOptions();

        $this->assertFalse($options['inclusive_end']);
    }

    #[Test]
    public function it_sets_stable(): void
    {
        $query = ViewQuery::create('app', 'by_status')
            ->stable();

        $options = $query->getOptions();

        $this->assertTrue($options['stable']);
    }

    #[Test]
    public function it_sets_stale(): void
    {
        $query = ViewQuery::create('app', 'by_status')
            ->stale('ok');

        $options = $query->getOptions();

        $this->assertSame('ok', $options['stale']);
    }

    #[Test]
    public function it_supports_compound_keys(): void
    {
        $query = ViewQuery::create('app', 'by_user_and_date')
            ->key(['user-123', '2024-01-15']);

        $options = $query->getOptions();

        $this->assertSame(['user-123', '2024-01-15'], $options['key']);
    }

    #[Test]
    public function it_builds_complex_view_query(): void
    {
        $query = ViewQuery::create('reports', 'sales_by_region')
            ->range(['2024-01-01', 'USA'], ['2024-12-31', 'USA'])
            ->includeDocs()
            ->limit(1000)
            ->descending();

        $options = $query->getOptions();

        $this->assertSame(['2024-01-01', 'USA'], $options['startkey']);
        $this->assertSame(['2024-12-31', 'USA'], $options['endkey']);
        $this->assertTrue($options['include_docs']);
        $this->assertSame(1000, $options['limit']);
        $this->assertTrue($options['descending']);
    }
}
