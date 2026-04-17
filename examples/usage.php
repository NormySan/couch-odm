<?php

declare(strict_types=1);

use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Embedded;
use SmrtSystems\Couch\Attribute\EmbeddedCollection;
use SmrtSystems\Couch\Attribute\EmbeddedDocument;
use SmrtSystems\Couch\Attribute\Field;
use SmrtSystems\Couch\Attribute\Id;
use SmrtSystems\Couch\Cache\DocumentCache;
use SmrtSystems\Couch\Client\CouchDbClient;
use SmrtSystems\Couch\DocumentManager;
use SmrtSystems\Couch\Hydration\DocumentMapper;
use SmrtSystems\Couch\Hydration\Hydrator;
use SmrtSystems\Couch\Mapping\MetadataFactory;
use SmrtSystems\Couch\Query\FindQuery;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\HttpClient;

require __DIR__ . '/../vendor/autoload.php';

// ──────────────────────────────────────────────
// Embedded Documents
// ──────────────────────────────────────────────

#[EmbeddedDocument]
class Address
{
    #[Field]
    public string $street;

    #[Field]
    public string $city;

    #[Field]
    public string $postalCode;

    public function __construct(string $street, string $city, string $postalCode)
    {
        $this->street = $street;
        $this->city = $city;
        $this->postalCode = $postalCode;
    }
}

#[EmbeddedDocument]
class Note
{
    #[Field]
    public string $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }
}

#[EmbeddedDocument]
class Item
{
    #[Field]
    public string $type;

    #[Field]
    public string $service;

    #[Field]
    public string $unitPrice;

    #[EmbeddedCollection(Note::class)]
    public array $notes;

    public function __construct(
        string $type,
        string $service,
        string $unitPrice,
        ?array $notes = [],
    ) {
        $this->type = $type;
        $this->service = $service;
        $this->unitPrice = $unitPrice;
        $this->notes = $notes;
    }
}

// ──────────────────────────────────────────────
// Documents
// ──────────────────────────────────────────────

#[Document('customers')]
class Customer
{
    #[Id]
    public readonly string $id;

    #[Field]
    public string $name;

    #[Field]
    public string $phone;

    #[Field]
    public string $email;

    #[Embedded(Address::class)]
    public Address $address;

    #[Field(name: 'created_at')]
    public readonly DateTimeImmutable $createdAt;

    public function __construct(string $name, string $phone, string $email, Address $address)
    {
        $this->name = $name;
        $this->phone = $phone;
        $this->email = $email;
        $this->address = $address;
        $this->createdAt = new DateTimeImmutable();
    }
}

#[Document('orders')]
class Order
{
    #[Id]
    public readonly string $id;

    #[Field]
    public readonly string $orderNumber;

    #[Field]
    public string $customerId;

    #[Field]
    public string $status = 'received' {
        set(string $status) {
            if (!in_array($status, ['received', 'in_progress', 'completed'])) {
                throw new \InvalidArgumentException('Invalid order status');
            }
            $this->status = $status;
        }
    }

    /** @var list<Item> */
    #[EmbeddedCollection(targetClass: Item::class)]
    public array $items = [];

    #[Field(name: 'pickup_date')]
    public DateTimeImmutable $pickupDate;

    #[Field(name: 'created_at')]
    public readonly DateTimeImmutable $createdAt;

    public function __construct(
        Customer $customer,
        DateTimeImmutable $pickupDate
    ) {
        $this->orderNumber = 'DC-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $this->customerId = $customer->id;
        $this->pickupDate = $pickupDate;
        $this->createdAt = new DateTimeImmutable();
    }

    public function total(): float
    {
        return array_sum(array_map(fn(Item $item) => $item->unitPrice, $this->items));
    }
}

// ──────────────────────────────────────────────
// Bootstrap
// ──────────────────────────────────────────────

$dm = new DocumentManager(
    client: new CouchDbClient(HttpClient::create(), 'http://localhost:5984', 'admin', 'password'),
    mapper: new DocumentMapper(new MetadataFactory(), new Hydrator()),
    cache: new DocumentCache(new Psr16Cache(new ArrayAdapter())),
);

// ──────────────────────────────────────────────
// Create a customer
// ──────────────────────────────────────────────

$customer = new Customer(
    name: 'Jane Doe',
    phone: '+1-555-0123',
    email: 'jane@example.com',
    address: new Address('742 Evergreen Terrace', 'Springfield', '62704'),
);

$dm->persist($customer);
$dm->flush();

echo "Customer created: {$customer->name} ({$customer->id})\n";

// ──────────────────────────────────────────────
// Create a dry cleaning order
// ──────────────────────────────────────────────

$order = new Order(
    customer: $customer,
    pickupDate: new DateTimeImmutable('+3 days'),
);

$order->items[] = new Item('Suit Jacket', 'dry_clean', '18.50');
$order->items[] = new Item('Dress Shirt', 'wash_and_press', '6.00', notes: [new Note('Light starch')]);
$order->items[] = new Item('Silk Dress', 'dry_clean', '22.00', notes: [new Note('Red wine stain on front')]);
$order->items[] = new Item('Trousers', 'press', '8.50');

$dm->persist($order);
$dm->flush();

echo "Order created: {$order->orderNumber} - {$order->status} - \${$order->total()}\n";

// ──────────────────────────────────────────────
// Fetch by ID
// ──────────────────────────────────────────────

$fetched = $dm->get(Order::class, $order->id);
echo "Fetched order: {$fetched->orderNumber} with " . count($fetched->items) . " items\n";

// ──────────────────────────────────────────────
// Query with Mango (findBy)
// ──────────────────────────────────────────────

$query = FindQuery::create()
    ->where('status', 'received')
    ->orderBy('created_at', 'desc')
    ->limit(10);

echo "\nPending orders:\n";
foreach ($dm->findBy(Order::class, $query) as $pending) {
    echo "  - {$pending->orderNumber}: {$pending->status} ({$pending->pickupDate->format('M j')})\n";
}

// ──────────────────────────────────────────────
// Update status
// ──────────────────────────────────────────────

$order->status = 'in_progress';
$dm->persist($order);
$dm->flush();

echo "\nOrder {$order->orderNumber} updated to: {$order->status}\n";

// ──────────────────────────────────────────────
// Remove
// ──────────────────────────────────────────────

//$dm->remove($order);
//$dm->flush();
