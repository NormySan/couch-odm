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
use SmrtSystems\Couch\Type\TypeConverterInterface;
use SmrtSystems\Couch\Type\TypeConverterRegistry;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\HttpClient;

require __DIR__ . '/../vendor/autoload.php';

// ──────────────────────────────────────────────
// Value Objects
// ──────────────────────────────────────────────

final class Money
{
    public function __construct(
        public readonly string $amount,
        public readonly string $currency = 'USD',
    ) {}

    public static function USD(string $amount): self
    {
        return new self($amount, 'USD');
    }

    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException("Cannot add different currencies: {$this->currency} and {$other->currency}");
        }

        return new self(bcadd($this->amount, $other->amount, 2), $this->currency);
    }

    public function format(): string
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        return $formatter->formatCurrency((float) $this->amount, $this->currency);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}

final class MoneyConverter implements TypeConverterInterface
{
    public function getPhpType(): string
    {
        return Money::class;
    }

    public function toDatabaseValue(mixed $value): ?array
    {
        if (!$value instanceof Money) {
            return null;
        }

        return [
            'amount' => $value->amount,
            'currency' => $value->currency,
        ];
    }

    public function toPhpValue(mixed $value): ?Money
    {
        if (!is_array($value) || !isset($value['amount'], $value['currency'])) {
            return null;
        }

        return new Money((string) $value['amount'], (string) $value['currency']);
    }
}

// ──────────────────────────────────────────────
// Embedded Documents
// ──────────────────────────────────────────────

#[EmbeddedDocument]
class Address implements Stringable
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

    public function __toString(): string
    {
        return implode(', ', [$this->street, $this->city, $this->postalCode]);
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
    public Money $unitPrice;

    #[EmbeddedCollection(Note::class)]
    public array $notes;

    public function __construct(
        string $type,
        string $service,
        Money $unitPrice,
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
class Customer implements Stringable
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

    public function __toString(): string
    {
        return "$this->name ($this->id)";
    }
}

enum OrderStatus: string {
    case Received = 'received';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function canTransitionTo(self $status): bool {
        return match ($this) {
            self::Received => in_array($status, [self::InProgress, self::Completed]),
            self::InProgress => $status == self::Completed,
            self::Completed => false,
        };
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
    public OrderStatus $status = OrderStatus::Received {
        set(OrderStatus $status) {
            if (!$this->status->canTransitionTo($status)) {
                throw new \InvalidArgumentException("Cannot transition from {$this->status->value} to {$status->value}");
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

    public function total(): Money
    {
        return array_reduce(
            $this->items,
            fn(Money $carry, Item $item) => $carry->add($item->unitPrice),
            Money::USD('0.00'),
        );
    }
}

// ──────────────────────────────────────────────
// Bootstrap
// ──────────────────────────────────────────────

$typeRegistry = new TypeConverterRegistry([new MoneyConverter()]);

$cache = new Psr16Cache(new ChainAdapter(
    adapters: [
        new ArrayAdapter(),
        new RedisAdapter(redis: RedisAdapter::createConnection('redis://127.0.0.1:6379')),
    ],
    defaultLifetime: 60 * 60,
));

$dm = new DocumentManager(
    client: new CouchDbClient(HttpClient::create(), 'http://localhost:5984', 'user', 'password'),
    mapper: new DocumentMapper(new MetadataFactory(typeConverterRegistry: $typeRegistry), new Hydrator(), $typeRegistry),
    cache: new DocumentCache($cache),
);

// ──────────────────────────────────────────────
// Create a customer
// ──────────────────────────────────────────────

$customer = new Customer(
    name: 'Thomas A. Anderson',
    phone: '+1-555-0123',
    email: 'thomas.a@example.com',
    address: new Address('742 Evergreen Terrace', 'Springfield', '62704'),
);

$dm->persist($customer);
$dm->flush();

echo "Customer created ({$customer->id}):\n";
echo "    name: $customer->name\n";
echo "    email: $customer->email\n";
echo "    phone: $customer->phone\n";
echo "    address: $customer->address\n\n";

// ──────────────────────────────────────────────
// Create a dry cleaning order
// ──────────────────────────────────────────────

$order = new Order(
    customer: $customer,
    pickupDate: new DateTimeImmutable('+3 days'),
);

$order->items[] = new Item('Suit Jacket', 'dry_clean', Money::USD('18.50'));
$order->items[] = new Item('Dress Shirt', 'wash_and_press', Money::USD('6.00'), notes: [new Note('Light starch')]);
$order->items[] = new Item('Silk Dress', 'dry_clean', Money::USD('22.00'), notes: [new Note('Red wine stain on front')]);
$order->items[] = new Item('Trousers', 'press', Money::USD('8.50'));

$dm->persist($order);
$dm->flush();

$orderItemsCount = count($order->items);

echo "Order created ($order->id):\n";
echo "    number: $order->orderNumber\n";
echo "    customer: $customer\n";
echo "    status: {$order->status->value}\n";
echo "    total: {$order->total()}\n";
echo "    items: $orderItemsCount\n\n";

// ──────────────────────────────────────────────
// Fetch by ID
// ──────────────────────────────────────────────

$fetchedOrder = $dm->get(Order::class, $order->id);
$fetchedCustomer = $dm->get(Customer::class, $fetchedOrder->customerId);

echo "Fetched order ($fetchedOrder->id):\n";
echo "    number: $fetchedOrder->orderNumber\n";
echo "    customer: $fetchedCustomer\n";
echo "    status: {$fetchedOrder->status->value}\n";
echo "    total: {$fetchedOrder->total()}\n";
echo "    items:\n";

foreach ($fetchedOrder->items as $index => $item) {
    $count = $index + 1;
    echo "        item $count: $item->type\n";
    echo "            service: $item->service\n";
    echo "            price: {$item->unitPrice->format()}\n";
}

echo "\n";

// ──────────────────────────────────────────────
// Update status
// ──────────────────────────────────────────────

$previousOrderStatus = $fetchedOrder->status;

$fetchedOrder->status = OrderStatus::InProgress;
$dm->persist($fetchedOrder);
$dm->flush();

echo "Changed order status ($fetchedOrder->id):\n";
echo "    transition: $previousOrderStatus->value -> {$fetchedOrder->status->value}\n\n";

$previousOrderStatus = $fetchedOrder->status;

$fetchedOrder->status = OrderStatus::Completed;
$dm->persist($fetchedOrder);
$dm->flush();

echo "Changed order status ($fetchedOrder->id):\n";
echo "    transition: $previousOrderStatus->value -> {$fetchedOrder->status->value}\n\n";

echo "🚚 Order ready for pickup or delivery!\n";

// ──────────────────────────────────────────────
// Remove
// ──────────────────────────────────────────────

//$dm->remove($order);
//$dm->flush();
