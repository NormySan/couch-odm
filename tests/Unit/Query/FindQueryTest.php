<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Unit\Query;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\Query\FindQuery;

final class FindQueryTest extends TestCase
{
    #[Test]
    public function it_creates_empty_query(): void
    {
        $query = FindQuery::create();

        $this->assertSame([], $query->getSelector());
        $this->assertSame([], $query->getOptions());
    }

    #[Test]
    public function it_adds_equality_condition(): void
    {
        $query = FindQuery::create()
            ->where('status', 'active');

        $this->assertSame(['status' => ['$eq' => 'active']], $query->getSelector());
    }

    #[Test]
    public function it_adds_operator_condition(): void
    {
        $query = FindQuery::create()
            ->where('age', '$gte', 18);

        $this->assertSame(['age' => ['$gte' => 18]], $query->getSelector());
    }

    #[Test]
    public function it_chains_multiple_conditions(): void
    {
        $query = FindQuery::create()
            ->where('status', 'active')
            ->andWhere('age', '$gte', 18)
            ->where('country', 'USA');

        $selector = $query->getSelector();

        $this->assertSame(['$eq' => 'active'], $selector['status']);
        $this->assertSame(['$gte' => 18], $selector['age']);
        $this->assertSame(['$eq' => 'USA'], $selector['country']);
    }

    #[Test]
    public function it_adds_raw_selector(): void
    {
        $query = FindQuery::create()
            ->whereRaw(['$or' => [
                ['status' => 'active'],
                ['status' => 'pending'],
            ]]);

        $selector = $query->getSelector();

        $this->assertArrayHasKey('$or', $selector);
        $this->assertCount(2, $selector['$or']);
    }

    #[Test]
    public function it_sets_limit(): void
    {
        $query = FindQuery::create()
            ->where('status', 'active')
            ->limit(10);

        $options = $query->getOptions();

        $this->assertSame(10, $options['limit']);
    }

    #[Test]
    public function it_sets_skip(): void
    {
        $query = FindQuery::create()
            ->where('status', 'active')
            ->skip(20);

        $options = $query->getOptions();

        $this->assertSame(20, $options['skip']);
    }

    #[Test]
    public function it_adds_sort(): void
    {
        $query = FindQuery::create()
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->orderBy('name', 'asc');

        $options = $query->getOptions();

        $this->assertSame([
            ['created_at' => 'desc'],
            ['name' => 'asc'],
        ], $options['sort']);
    }

    #[Test]
    public function it_adds_multiple_sorts(): void
    {
        $query = FindQuery::create()
            ->orderByMultiple([
                'created_at' => 'desc',
                'name' => 'asc',
            ]);

        $options = $query->getOptions();

        $this->assertSame([
            ['created_at' => 'desc'],
            ['name' => 'asc'],
        ], $options['sort']);
    }

    #[Test]
    public function it_selects_fields(): void
    {
        $query = FindQuery::create()
            ->where('status', 'active')
            ->select(['_id', 'name', 'email']);

        $options = $query->getOptions();

        $this->assertSame(['_id', 'name', 'email'], $options['fields']);
    }

    #[Test]
    public function it_sets_bookmark(): void
    {
        $query = FindQuery::create()
            ->where('status', 'active')
            ->bookmark('g1AAAABweJzLYWBg');

        $options = $query->getOptions();

        $this->assertSame('g1AAAABweJzLYWBg', $options['bookmark']);
    }

    #[Test]
    public function it_sets_use_index(): void
    {
        $query = FindQuery::create()
            ->where('status', 'active')
            ->useIndex('status-index');

        $options = $query->getOptions();

        $this->assertSame('status-index', $options['use_index']);
    }

    #[Test]
    public function it_enables_execution_stats(): void
    {
        $query = FindQuery::create()
            ->where('status', 'active')
            ->withExecutionStats();

        $options = $query->getOptions();

        $this->assertTrue($options['execution_stats']);
    }

    #[Test]
    public function it_builds_complex_query(): void
    {
        $query = FindQuery::create()
            ->where('status', 'active')
            ->where('age', '$gte', 18)
            ->where('country', '$in', ['USA', 'Canada'])
            ->orderBy('created_at', 'desc')
            ->limit(25)
            ->skip(50);

        $selector = $query->getSelector();
        $options = $query->getOptions();

        $this->assertSame(['$eq' => 'active'], $selector['status']);
        $this->assertSame(['$gte' => 18], $selector['age']);
        $this->assertSame(['$in' => ['USA', 'Canada']], $selector['country']);
        $this->assertSame(25, $options['limit']);
        $this->assertSame(50, $options['skip']);
    }
}
