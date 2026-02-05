<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Unit\Cache;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SmrtSystems\Couch\Cache\DocumentCache;

final class DocumentCacheTest extends TestCase
{
    private DocumentCache $cache;

    protected function setUp(): void
    {
        $this->cache = new DocumentCache();
    }

    #[Test]
    public function it_stores_and_retrieves_document(): void
    {
        $data = ['_id' => 'doc-1', '_rev' => '1-abc', 'name' => 'Test'];

        $this->cache->set('mydb', 'doc-1', $data);
        $retrieved = $this->cache->get('mydb', 'doc-1');

        $this->assertSame($data, $retrieved);
    }

    #[Test]
    public function it_returns_null_for_missing_document(): void
    {
        $this->assertNull($this->cache->get('mydb', 'nonexistent'));
    }

    #[Test]
    public function it_checks_if_document_exists(): void
    {
        $this->cache->set('mydb', 'doc-1', ['_id' => 'doc-1']);

        $this->assertTrue($this->cache->has('mydb', 'doc-1'));
        $this->assertFalse($this->cache->has('mydb', 'nonexistent'));
    }

    #[Test]
    public function it_deletes_document(): void
    {
        $this->cache->set('mydb', 'doc-1', ['_id' => 'doc-1']);
        $this->assertTrue($this->cache->has('mydb', 'doc-1'));

        $this->cache->delete('mydb', 'doc-1');

        $this->assertFalse($this->cache->has('mydb', 'doc-1'));
        $this->assertNull($this->cache->get('mydb', 'doc-1'));
    }

    #[Test]
    public function it_retrieves_multiple_documents(): void
    {
        $this->cache->set('mydb', 'doc-1', ['_id' => 'doc-1', 'name' => 'First']);
        $this->cache->set('mydb', 'doc-2', ['_id' => 'doc-2', 'name' => 'Second']);
        $this->cache->set('mydb', 'doc-3', ['_id' => 'doc-3', 'name' => 'Third']);

        $results = $this->cache->getMultiple('mydb', ['doc-1', 'doc-2', 'doc-4']);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey('doc-1', $results);
        $this->assertArrayHasKey('doc-2', $results);
        $this->assertArrayNotHasKey('doc-4', $results);
    }

    #[Test]
    public function it_returns_empty_array_for_empty_ids(): void
    {
        $results = $this->cache->getMultiple('mydb', []);

        $this->assertSame([], $results);
    }

    #[Test]
    public function it_clears_all_documents(): void
    {
        $this->cache->set('mydb', 'doc-1', ['_id' => 'doc-1']);
        $this->cache->set('mydb', 'doc-2', ['_id' => 'doc-2']);
        $this->cache->set('otherdb', 'doc-3', ['_id' => 'doc-3']);

        $this->cache->clear();

        $this->assertNull($this->cache->get('mydb', 'doc-1'));
        $this->assertNull($this->cache->get('mydb', 'doc-2'));
        $this->assertNull($this->cache->get('otherdb', 'doc-3'));
    }

    #[Test]
    public function it_separates_documents_by_database(): void
    {
        $this->cache->set('db1', 'doc-1', ['_id' => 'doc-1', 'db' => 'db1']);
        $this->cache->set('db2', 'doc-1', ['_id' => 'doc-1', 'db' => 'db2']);

        $fromDb1 = $this->cache->get('db1', 'doc-1');
        $fromDb2 = $this->cache->get('db2', 'doc-1');

        $this->assertSame('db1', $fromDb1['db']);
        $this->assertSame('db2', $fromDb2['db']);
    }

    #[Test]
    public function it_handles_special_characters_in_ids(): void
    {
        $specialId = 'user:john@example.com/profile#main';
        $data = ['_id' => $specialId, 'type' => 'user'];

        $this->cache->set('mydb', $specialId, $data);
        $retrieved = $this->cache->get('mydb', $specialId);

        $this->assertSame($data, $retrieved);
    }

    #[Test]
    public function it_overwrites_existing_document(): void
    {
        $this->cache->set('mydb', 'doc-1', ['_id' => 'doc-1', '_rev' => '1-abc', 'version' => 1]);
        $this->cache->set('mydb', 'doc-1', ['_id' => 'doc-1', '_rev' => '2-def', 'version' => 2]);

        $retrieved = $this->cache->get('mydb', 'doc-1');

        $this->assertSame('2-def', $retrieved['_rev']);
        $this->assertSame(2, $retrieved['version']);
    }
}
