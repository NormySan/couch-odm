# Couch ODM

A simple object data mapper for CouchDB using attributes to define documents and fields. It supports deeply nested data structures, enums and value objects.

**Note that this package is very a work in progress and should not be used inproduction. A lot of additional features are planned and some of the existing features will be reworked to be more in line with how CouchDB works.**

## Installation

```bash
composer require smrt-systems/couch-odm
```

Requires PHP 8.4+ and a reachable CouchDB instance.

## Setup

Define a document using PHP attributes:

```php
use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Field;
use SmrtSystems\Couch\Attribute\Id;

#[Document('customers')]
class Customer
{
    #[Id]
    public readonly string $id;

    #[Field]
    public string $name;

    #[Field]
    public string $email;

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }
}
```

Bootstrap a `DocumentManager`:

```php
use SmrtSystems\Couch\Client\CouchDbClient;
use SmrtSystems\Couch\DocumentManager;
use SmrtSystems\Couch\Hydration\DocumentMapper;
use SmrtSystems\Couch\Hydration\Hydrator;
use SmrtSystems\Couch\Mapping\MetadataFactory;
use SmrtSystems\Couch\Type\TypeConverterRegistry;
use Symfony\Component\HttpClient\HttpClient;

$typeRegistry = new TypeConverterRegistry();

$dm = new DocumentManager(
    client: new CouchDbClient(HttpClient::create(), 'http://localhost:5984', 'user', 'password'),
    mapper: new DocumentMapper(new MetadataFactory($typeRegistry), new Hydrator(), $typeRegistry),
);
```

## Persisting documents

```php
$customer = new Customer('Thomas A. Anderson', 'thomas.a@example.com');

$dm->persist($customer);
$dm->flush();
```

Updates work the same way — persist a loaded document and call `flush()`. To delete, use `$dm->remove($customer)` followed by `$dm->flush()`.

## Fetching data

### Get by ID

Returns the document or `null`.

```php
$customer = $dm->get(Customer::class, $id);
```

### Find by selector (Mango)

Use `FindQuery` for CouchDB's `_find` endpoint.

```php
use SmrtSystems\Couch\Query\FindQuery;

$query = FindQuery::create()
    ->where('email', 'thomas.a@example.com')
    ->limit(10);

foreach ($dm->findBy(Customer::class, $query) as $customer) {
    // ...
}
```

Operators map directly to Mango:

```php
FindQuery::create()
    ->where('age', '$gte', 18)
    ->where('tags', '$in', ['vip', 'staff'])
    ->orderBy('name', 'asc');
```

### All documents (`_all_docs`)

Use `AllQuery` to fetch a range of documents by ID.

```php
use SmrtSystems\Couch\Query\AllQuery;

$query = AllQuery::create()
    ->range('customer:', 'customer:' . AllQuery::HIGH_UNICODE_CHARACTER)
    ->limit(50);

foreach ($dm->all(Customer::class, $query) as $customer) {
    // ...
}
```

### Views

Use `ViewQuery` to read from a design document view. Results are hydrated into the given class, so the view should emit full documents via `include_docs`.

```php
use SmrtSystems\Couch\Query\ViewQuery;

$query = ViewQuery::create('customers', 'by_city')
    ->key('Springfield')
    ->limit(20);

foreach ($dm->view(Customer::class, $query) as $customer) {
    // ...
}
```

## Additional configuration

### Caching

Pass any PSR-16 cache to the `DocumentManager` to cache documents by ID. Fetches by ID hit the cache before CouchDB; writes and deletes invalidate it automatically.

```php
use SmrtSystems\Couch\Cache\DocumentCache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

$cache = new Psr16Cache(new ChainAdapter([
    new ArrayAdapter(),
    new RedisAdapter(RedisAdapter::createConnection('redis://127.0.0.1:6379')),
], defaultLifetime: 3600));

$dm = new DocumentManager(
    client: new CouchDbClient(HttpClient::create(), 'http://localhost:5984', 'user', 'password'),
    mapper: new DocumentMapper(new MetadataFactory($typeRegistry), new Hydrator(), $typeRegistry),
    cache: new DocumentCache($cache),
);
```

### Custom type converters

Implement `TypeConverterInterface` to map value objects to and from stored data, then register them with the `TypeConverterRegistry`:

```php
use SmrtSystems\Couch\Type\TypeConverterRegistry;

$typeRegistry = new TypeConverterRegistry([new MoneyConverter()]);
```

### Embedded documents

Use `#[EmbeddedDocument]` for nested value types, with `#[Embedded]` for a single instance and `#[EmbeddedCollection]` for an array. See `examples/usage.php` for a complete walkthrough.
