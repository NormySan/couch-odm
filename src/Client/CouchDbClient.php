<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Client;

use Generator;
use SmrtSystems\Couch\Client\Data\CreateDesignDocumentInput;
use SmrtSystems\Couch\Client\Response\BulkResponse;
use SmrtSystems\Couch\Client\Response\DocumentResponse;
use SmrtSystems\Couch\Exception\ConflictException;
use SmrtSystems\Couch\Exception\CouchDbException;
use SmrtSystems\Couch\Exception\DocumentNotFoundException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * CouchDB HTTP client implementation using Symfony HttpClient.
 */
final class CouchDbClient implements CouchDbClientInterface
{
    private ?string $authCookie = null;
    private ?int $authCookieExpires = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUri,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly int $cookieLifetime = 540, // 9 minutes (CouchDB default is 10)
    ) {}

    public function get(string $database, string $id): DocumentResponse
    {
        $response = $this->request('GET', sprintf('/%s/%s', $database, urlencode($id)));

        if ($response->getStatusCode() === 404) {
            throw new DocumentNotFoundException($database, $id);
        }

        $this->assertSuccessfulResponse($response, $database);

        return new DocumentResponse($response->toArray());
    }

    public function put(string $database, string $id, array $data): DocumentResponse
    {
        $response = $this->request('PUT', sprintf('/%s/%s', $database, urlencode($id)), [
            'json' => $data,
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode === 409) {
            throw new ConflictException($database, $id, $data['_rev'] ?? null);
        }

        $this->assertSuccessfulResponse($response, $database);

        $result = $response->toArray();

        return new DocumentResponse([
            ...$data,
            '_id' => $result['id'],
            '_rev' => $result['rev'],
        ]);
    }

    public function delete(string $database, string $id, string $rev): bool
    {
        $response = $this->request('DELETE', sprintf('/%s/%s', $database, urlencode($id)), [
            'query' => ['rev' => $rev],
        ]);

        if ($response->getStatusCode() === 409) {
            throw new ConflictException($database, $id, $rev);
        }

        return $response->getStatusCode() === 200;
    }

    public function find(string $database, array $selector, array $options = []): iterable
    {
        $body = ['selector' => $selector, ...$options];

        $response = $this->request('POST', sprintf('/%s/_find', $database), [
            'json' => $body,
        ]);

        $this->assertSuccessfulResponse($response, $database);

        $result = $response->toArray();

        foreach ($result['docs'] ?? [] as $doc) {
            yield $doc;
        }
    }

    public function allDocs(string $database, array $options = []): iterable
    {
        $queryParams = [];
        $body = null;

        // Handle 'keys' option via POST body
        if (isset($options['keys'])) {
            $body = ['keys' => $options['keys']];
            unset($options['keys']);
        }

        // Convert boolean options to JSON strings
        foreach (['include_docs', 'descending', 'inclusive_end', 'update_seq'] as $boolOption) {
            if (isset($options[$boolOption])) {
                $queryParams[$boolOption] = $options[$boolOption] ? 'true' : 'false';
                unset($options[$boolOption]);
            }
        }

        // JSON encode key options
        foreach (['startkey', 'endkey', 'key'] as $keyOption) {
            if (isset($options[$keyOption])) {
                $queryParams[$keyOption] = json_encode($options[$keyOption]);
                unset($options[$keyOption]);
            }
        }

        // Pass through other options (limit, skip, etc.)
        $queryParams = [...$queryParams, ...$options];

        $requestOptions = [];
        if ($queryParams !== []) {
            $requestOptions['query'] = $queryParams;
        }
        if ($body !== null) {
            $requestOptions['json'] = $body;
        }

        $method = $body !== null ? 'POST' : 'GET';
        $response = $this->request($method, sprintf('/%s/_all_docs', $database), $requestOptions);

        $this->assertSuccessfulResponse($response, $database);

        $result = $response->toArray();

        foreach ($result['rows'] ?? [] as $row) {
            // If include_docs was true, yield the document; otherwise yield the row
            if (isset($row['doc'])) {
                yield $row['doc'];
            } else {
                yield $row;
            }
        }
    }

    public function view(string $database, string $design, string $view, array $options = []): iterable
    {
        $uri = sprintf('/%s/_design/%s/_view/%s', $database, $design, $view);

        $queryParams = $this->prepareViewOptions($options);
        $body = null;

        // Handle 'keys' option via POST body
        if (isset($options['keys'])) {
            $body = ['keys' => $options['keys']];
        }

        $requestOptions = [];
        if ($queryParams !== []) {
            $requestOptions['query'] = $queryParams;
        }
        if ($body !== null) {
            $requestOptions['json'] = $body;
        }

        $method = $body !== null ? 'POST' : 'GET';
        $response = $this->request($method, $uri, $requestOptions);

        $this->assertSuccessfulResponse($response, $database);

        $result = $response->toArray();

        foreach ($result['rows'] ?? [] as $row) {
            yield $row;
        }
    }

    public function bulk(string $database, array $docs): BulkResponse
    {
        $response = $this->request('POST', sprintf('/%s/_bulk_docs', $database), [
            'json' => ['docs' => $docs],
        ]);

        $this->assertSuccessfulResponse($response, $database);

        return new BulkResponse($response->toArray());
    }

    public function databaseExists(string $database): bool
    {
        $response = $this->request('HEAD', sprintf('/%s', $database));

        return $response->getStatusCode() === 200;
    }

    public function createDatabase(string $database): bool
    {
        $response = $this->request('PUT', sprintf('/%s', $database));

        return $response->getStatusCode() === 201;
    }

    public function deleteDatabase(string $database): bool
    {
        $response = $this->request('DELETE', sprintf('/%s', $database));

        return $response->getStatusCode() === 200;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        $options['base_uri'] = $this->baseUri;

        // Handle authentication
        if ($this->username !== null && $this->password !== null) {
            $this->ensureAuthenticated();
            $options['headers']['Cookie'] = $this->authCookie;
        }

        return $this->httpClient->request($method, $uri, $options);
    }

    private function ensureAuthenticated(): void
    {
        // Check if we have a valid cookie
        if ($this->authCookie !== null && $this->authCookieExpires !== null) {
            if (time() < $this->authCookieExpires) {
                return;
            }
        }

        $this->authenticate();
    }

    private function authenticate(): void
    {
        $response = $this->httpClient->request('POST', '/_session', [
            'base_uri' => $this->baseUri,
            'json' => [
                'name' => $this->username,
                'password' => $this->password,
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new CouchDbException(
                'Authentication failed',
                $response->getStatusCode(),
                $response->toArray(false)
            );
        }

        $cookies = $response->getHeaders()['set-cookie'] ?? [];
        foreach ($cookies as $cookie) {
            if (str_starts_with($cookie, 'AuthSession=')) {
                $this->authCookie = explode(';', $cookie)[0];
                $this->authCookieExpires = time() + $this->cookieLifetime;

                return;
            }
        }

        throw new CouchDbException('Authentication succeeded but no AuthSession cookie received');
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, string>
     */
    private function prepareViewOptions(array $options): array
    {
        $query = [];

        // Remove 'keys' as it's handled separately
        unset($options['keys']);

        // JSON encode key options
        foreach (['key', 'startkey', 'endkey', 'start_key', 'end_key'] as $jsonOption) {
            if (isset($options[$jsonOption])) {
                $query[$jsonOption] = json_encode($options[$jsonOption]);
                unset($options[$jsonOption]);
            }
        }

        // Boolean options
        foreach (['reduce', 'include_docs', 'descending', 'group', 'inclusive_end', 'update_seq', 'stable'] as $boolOption) {
            if (isset($options[$boolOption])) {
                $query[$boolOption] = $options[$boolOption] ? 'true' : 'false';
                unset($options[$boolOption]);
            }
        }

        // Integer options
        foreach (['limit', 'skip', 'group_level'] as $intOption) {
            if (isset($options[$intOption])) {
                $query[$intOption] = (string) $options[$intOption];
                unset($options[$intOption]);
            }
        }

        // Pass through any remaining options as-is
        foreach ($options as $key => $value) {
            if (is_scalar($value)) {
                $query[$key] = (string) $value;
            }
        }

        return $query;
    }

    private function assertSuccessfulResponse(ResponseInterface $response, string $database): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 200 && $statusCode < 300) {
            return;
        }

        $body = null;
        try {
            $body = $response->toArray(false);
        } catch (\Throwable) {
            // Ignore JSON parsing errors
        }

        throw CouchDbException::fromResponse($statusCode, $body);
    }

    public function getDesignDocument(string $database, string $name): array {
        $response = $this->request('GET', sprintf('/%s/_design/%s', $database, $name));
        return $response->toArray();
    }

    public function createDesignDocument(CreateDesignDocumentInput $input): array
    {
        $response = $this->request(
            method: 'PUT',
            uri: sprintf('/%s/_design/%s', $input->database, $input->name),
            options: [
                'json' => [
                    'language' => $input->language,
                    'views' => $input->views,
                ]
            ],
        );

        $content = $response->toArray(throw: false);

        if (!isset($content['id'], $content['rev'])) {
            throw new CouchDbException(
                'Failed to create design document',
                $response->getStatusCode(),
                $content
            );
        }

        return [
            'id' => $content['id'],
            'rev' => $content['rev'],
        ];
    }

    public function deleteDesignDocument(string $database, string $name): void
    {
        $document = $this->getDesignDocument($database, $name);

        $this->request(
            method: 'DELETE',
            uri: sprintf('/%s/_design/%s', $database, $name),
            options: [
                'query' => ['rev' => $document['_rev']]
            ],
        );
    }
}
