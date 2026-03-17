<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Tests\Integration\Helper;

use SmrtSystems\Couch\Client\CouchDbClient;
use SmrtSystems\Couch\Client\CouchDbClientInterface;
use SmrtSystems\Couch\DocumentManager;
use SmrtSystems\Couch\Hydration\DocumentMapper;
use SmrtSystems\Couch\Hydration\DocumentMapperInterface;
use SmrtSystems\Couch\Hydration\Hydrator;
use SmrtSystems\Couch\Mapping\MetadataFactory;
use Symfony\Component\HttpClient\HttpClient;

trait IntegrationTestHelper {

    public function createClient(): CouchDbClientInterface {
        return new CouchDbClient(
            httpClient: HttpClient::create(),
            baseUri: 'http://localhost:5984',
            username: 'user',
            password: 'password',
        );
    }

    public function createDocumentMapper(): DocumentMapperInterface {
        return new DocumentMapper(
            metadataFactory: new MetadataFactory(),
            hydrator: new Hydrator(),
        );
    }

    public function createDocumentManager(): DocumentManager {
        return new DocumentManager(
            client: $this->createClient(),
            mapper: $this->createDocumentMapper(),
        );
    }

}