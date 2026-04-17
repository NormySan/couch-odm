<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Integration\DocumentManager;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use SmrtSystems\Couch\DocumentManager;
use SmrtSystems\Couch\Query\ViewQuery;
use SmrtSystems\Couch\Tests\Fixtures\Order;

#[CoversMethod(DocumentManager::class, 'view')]
class DocumentManagerViewTest extends DocumentManagerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createOrders();

        $this->createDesignDocument(
            className: Order::class,
            name: 'orders',
            views: [
                'by_status' => [
                    'map' => 'function(doc) { emit(doc.status); }'
                ],
                'by_customer_and_status' => [
                    'map' => 'function(doc) { emit([doc.customerId, doc.status]); }'
                ],
            ],
        );
    }

    /**
     * @param list<string> $expectedOrderNumbers
     */
    #[DataProvider('viewDataProvider')]
    public function testView(ViewQuery $query, int $expectedCount, array $expectedOrderNumbers): void {
        $orders = iterator_to_array($this->manager->view(
            className: Order::class,
            query: $query,
        ));

        $orderNumbers = array_map(fn (Order $order) => $order->number, $orders);

        $this->assertCount($expectedCount, $orders);
        $this->assertSame($expectedOrderNumbers, $orderNumbers);
    }

    /**
     * @return array<string, mixed>
     */
    public static function viewDataProvider(): array
    {
        return [
            'Shipped orders' => [
                'query' => ViewQuery::create('orders', 'by_status')->key('shipped'),
                'expectedCount' => 2,
                'expectedOrderNumbers' => ['1', '2'],
            ],
            'Pending orders' => [
                'query' => ViewQuery::create('orders', 'by_status')->key('pending'),
                'expectedCount' => 3,
                'expectedOrderNumbers' => ['4', '5', '6'],
            ],
            'Processing orders' => [
                'query' => ViewQuery::create('orders', 'by_status')->key('processing'),
                'expectedCount' => 1,
                'expectedOrderNumbers' => ['3'],
            ],
            'Processing and pending orders' => [
                'query' => ViewQuery::create('orders', 'by_status')->keys(['processing', 'pending']),
                'expectedCount' => 4,
                'expectedOrderNumbers' => ['3', '4', '5', '6'],
            ],
            'Pending orders with a limit' => [
                'query' => ViewQuery::create('orders', 'by_status')
                    ->key('pending')
                    ->limit(2),
                'expectedCount' => 2,
                'expectedOrderNumbers' => ['4', '5'],
            ],
            'Customer 1 pending orders' => [
                'query' => ViewQuery::create('orders', 'by_customer_and_status')->key(['1', 'pending']),
                'expectedCount' => 2,
                'expectedOrderNumbers' => ['4', '5'],
            ],
            'Customer 1 orders' => [
                'query' => ViewQuery::create('orders', 'by_customer_and_status')
                    ->startKey('1')
                    ->endKey(['1', []]),
                'expectedCount' => 3,
                'expectedOrderNumbers' => ['4', '5', '1'],
            ],
        ];
    }

    private function createOrders(): void {
        $this->persistFlushAndTrack([
            new Order(number: '1', status: 'shipped', customerId: '1',),
            new Order(number: '2', status: 'shipped', customerId: '2'),
            new Order(number: '3', status: 'processing', customerId: '3'),
            new Order(number: '4', status: 'pending', customerId: '1'),
            new Order(number: '5', status: 'pending', customerId: '1'),
            new Order(number: '6', status: 'pending', customerId: '4'),
        ]);
    }

}