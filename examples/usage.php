<?php

declare(strict_types=1);

use SmrtSystems\Couch\Attribute\Document;
use SmrtSystems\Couch\Attribute\Field;
use SmrtSystems\Couch\Attribute\Id;
use SmrtSystems\Couch\Attribute\Revision;
use SmrtSystems\Couch\Cache\DocumentCache;
use SmrtSystems\Couch\Client\CouchDbClient;
use SmrtSystems\Couch\DocumentManager;
use SmrtSystems\Couch\Hydration\DocumentMapper;
use SmrtSystems\Couch\Hydration\Hydrator;
use SmrtSystems\Couch\Mapping\MetadataFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\HttpClient;

require __DIR__ . '/../vendor/autoload.php';

// Interface with methods that allow you to dynamically configure various things for the ODM.
// @todo Add dynamically configurable cache key.
// @todo Add dynamically configurable database name.
// @todo Support multiple document types in the same database (discriminator).
// @todo ID is never set on the document when auto-generated on save.

#[Document('users')]
class User {

    #[Id]
    public readonly string $id;

    #[Revision]
    public string $rev;

    #[Field]
    public string $name;

    #[Field]
    public readonly DateTimeImmutable $createdAt;

    public function __construct(string $name) {
        $this->name = $name;
        $this->createdAt = new DateTimeImmutable();
    }

}

$cache = new Psr16Cache(new ChainAdapter(
    adapters: [
        new ArrayAdapter(),
        new RedisAdapter(redis: RedisAdapter::createConnection('redis://127.0.0.1:6379')),
    ],
    defaultLifetime: 60 * 60,
));

$dm = new DocumentManager(
    client: new CouchDbClient(HttpClient::create(), 'http://localhost:5984', 'user', 'password'),
    mapper: new DocumentMapper(new MetadataFactory(), new Hydrator()),
    cache: new DocumentCache($cache),
);

$user = new user(name: 'Spiderman');
$dm->persist($user);
$dm->flush();
var_dump($user);

//var_dump($dm->find(User::class, '283e4062-9532-4e3a-ae11-17624e559c31'));

// // Find by ID
// $user = $dm->find(User::class, 'user-123');

// // Query with Mango
// $activeUsers = $dm->findBy(User::class,
//     FindQuery::create()
//         ->where('status', 'active')
//         ->where('age', '$gte', 18)
//         ->orderBy('created_at', 'desc')
//         ->limit(10)
// );

// // Persist
// $user->name = 'Updated Name';
// $dm->persist($user);
// $dm->flush();