<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Integration\DocumentManager;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use SmrtSystems\Couch\DocumentManager;
use SmrtSystems\Couch\Query\AllQuery;
use SmrtSystems\Couch\Tests\Fixtures\Order;

#[CoversMethod(DocumentManager::class, 'all')]
class DocumentManagerAllTest extends DocumentManagerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createOrders();
    }

    /**
     * @param array<string> $expectedOrderNumbers
     */
    #[DataProvider('allDataProvider')]
    public function testAll(AllQuery $query, int $expectedCount, array $expectedOrderNumbers): void {
        $result = $this->manager->all(Order::class, $query);
        $orders = iterator_to_array($result);

        $this->assertCount($expectedCount, $orders);
        $this->assertSame($expectedOrderNumbers, array_map(fn (Order $order) => $order->number, $orders));
    }

    public static function allDataProvider(): array {
        return [
            'All orders in March' => [
                'query' => AllQuery::create()
                    ->startKey('order_2026-03-01_')
                    ->endKey('order_2026-03-31_' . AllQuery::HIGH_UNICODE_CHARACTER),
                'expectedCount' => 9,
                'expectedOrderNumbers' => ['01', '02', '03', '04', '05', '06', '07', '08', '09'],
            ],
            'All orders in March and April' => [
                'query' => AllQuery::create()
                    ->startKey('order_2026-03-01_')
                    ->endKey('order_2026-04-30_' . AllQuery::HIGH_UNICODE_CHARACTER),
                'expectedCount' => 16,
                'expectedOrderNumbers' => ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16'],
            ],
            'First 5 orders in March' => [
                'query' => AllQuery::create()
                    ->startKey('order_2026-03-01_')
                    ->endKey('order_2026-04-30_' . AllQuery::HIGH_UNICODE_CHARACTER)
                    ->limit(5),
                'expectedCount' => 5,
                'expectedOrderNumbers' => ['01', '02', '03', '04', '05'],
            ],
            'Skip first 4 orders in April and get the next 10' => [
                'query' => AllQuery::create()
                    ->startKey('order_2026-04-01_')
                    ->endKey('order_2026-04-30_' . AllQuery::HIGH_UNICODE_CHARACTER)
                    ->skip(4)
                    ->limit(10),
                'expectedCount' => 3,
                'expectedOrderNumbers' => ['14', '15', '16'],
            ],
            'All orders in May' => [
                'query' => AllQuery::create()
                    ->range(
                        startKey: 'order_2026-05-01_',
                        endKey: 'order_2026-05-31_' . AllQuery::HIGH_UNICODE_CHARACTER
                    ),
                'expectedCount' => 4,
                'expectedOrderNumbers' => ['17', '18', '19', '20'],
            ],
            'All orders in May without excluding the end key' => [
                'query' => AllQuery::create()
                    ->startKey('order_2026-05-03_17')
                    ->endKey('order_2026-05-25_20')
                    ->inclusiveEnd(false),
                'expectedCount' => 3,
                'expectedOrderNumbers' => ['17', '18', '19'],
            ],
            'All orders in May in a descending order' => [
                'query' => AllQuery::create()
                    ->startKey('order_2026-05-31_' . AllQuery::HIGH_UNICODE_CHARACTER)
                    ->endKey('order_2026-05-01_')
                    ->descending(),
                'expectedCount' => 4,
                'expectedOrderNumbers' => ['20', '19', '18', '17'],
            ],
            'All orders with IDs (keys)' => [
                'query' => AllQuery::create()->keys([
                    'order_2026-03-12_05',
                    'order_2026-04-08_12',
                    'order_2026-05-03_17',
                    'order_2026-05-25_20'
                ]),
                'expectedCount' => 4,
                'expectedOrderNumbers' => ['05', '12', '17', '20'],
            ],
        ];
    }

    private function createOrders(): void
    {
        $this->persistFlushAndTrack([
            new Order(number: '01', date: new DateTimeImmutable('2026-03-01 10:27')),
            new Order(number: '02', date: new DateTimeImmutable('2026-03-01 11:13')),
            new Order(number: '03', date: new DateTimeImmutable('2026-03-03 14:51')),
            new Order(number: '04', date: new DateTimeImmutable('2026-03-08 09:45')),
            new Order(number: '05', date: new DateTimeImmutable('2026-03-12 17:01')),
            new Order(number: '06', date: new DateTimeImmutable('2026-03-20 11:30')),
            new Order(number: '07', date: new DateTimeImmutable('2026-03-25 16:42')),
            new Order(number: '08', date: new DateTimeImmutable('2026-03-28 13:09')),
            new Order(number: '09', date: new DateTimeImmutable('2026-03-31 23:59')),
            new Order(number: '10', date: new DateTimeImmutable('2026-04-02 09:00')),
            new Order(number: '11', date: new DateTimeImmutable('2026-04-05 14:20')),
            new Order(number: '12', date: new DateTimeImmutable('2026-04-08 11:45')),
            new Order(number: '13', date: new DateTimeImmutable('2026-04-10 16:30')),
            new Order(number: '14', date: new DateTimeImmutable('2026-04-15 10:00')),
            new Order(number: '15', date: new DateTimeImmutable('2026-04-22 15:30')),
            new Order(number: '16', date: new DateTimeImmutable('2026-04-30 18:45')),
            new Order(number: '17', date: new DateTimeImmutable('2026-05-03 09:15')),
            new Order(number: '18', date: new DateTimeImmutable('2026-05-10 12:00')),
            new Order(number: '19', date: new DateTimeImmutable('2026-05-18 14:30')),
            new Order(number: '20', date: new DateTimeImmutable('2026-05-25 17:45')),
        ]);
    }
}