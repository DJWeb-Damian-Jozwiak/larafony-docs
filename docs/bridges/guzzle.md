---
title: "Guzzle HTTP Bridge"
description: "Integrate Guzzle HTTP client with Larafony for async requests, middleware, and PSR-18 compatibility."
---

# Guzzle HTTP Bridge

## Installation

```bash
composer require larafony/http-guzzle
```

## Configuration

Register the service provider in your `bootstrap.php`:

```php
use Larafony\Http\Guzzle\ServiceProviders\GuzzleServiceProvider;

$app->withServiceProviders([
GuzzleServiceProvider::class
]);
```

## Basic Usage

Inject `GuzzleHttpClient` into your controllers via dependency injection:

```php
use Larafony\Http\Guzzle\GuzzleHttpClient;
use Larafony\Framework\Web\Controller;
use Larafony\Framework\Routing\Advanced\Attributes\Route;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

final class ApiController extends Controller
{
#[Route('/fetch-users', methods: ['GET'])]
public function fetchUsers(GuzzleHttpClient $client): ResponseInterface
{
// Simple GET request
$response = $client->getGuzzle()->get('https://api.example.com/users');
$data = json_decode($response->getBody()->getContents(), true);

return new ResponseFactory()->createJsonResponse($data);
}

#[Route('/create-user', methods: ['POST'])]
public function createUser(GuzzleHttpClient $client, UserDto $dto): ResponseInterface
{
// POST with JSON body
$response = $client->post('https://api.example.com/users', [
'json' => $dto->toArray(),
]);

return new ResponseFactory()->createJsonResponse([
'status' => 'created',
'data' => json_decode($response->getBody()->getContents(), true),
], 201);
}
}
```

## Bearer Token Authentication

```php
use Larafony\Http\Guzzle\GuzzleHttpClientFactory;
use Larafony\Framework\Web\Config;

#[Route('/authorized-request', methods: ['GET'])]
public function authorized(): ResponseInterface
{
$token = Config::get('api.token');
$client = GuzzleHttpClientFactory::withBearerToken($token);

$response = $client->get('https://api.example.com/protected');

return new ResponseFactory()->createJsonResponse(
json_decode($response->getBody()->getContents(), true)
);
}
```

## Async Requests

Make concurrent requests for better performance:

```php
use GuzzleHttp\Promise\Utils;

#[Route('/dashboard-data', methods: ['GET'])]
public function dashboardData(GuzzleHttpClient $client): ResponseInterface
{
// Fire all requests concurrently
$promises = [
'users' => $client->getGuzzle()->getAsync('https://api.example.com/users'),
'orders' => $client->getGuzzle()->getAsync('https://api.example.com/orders'),
'stats' => $client->getGuzzle()->getAsync('https://api.example.com/stats'),
];

// Wait for all to complete
$results = Utils::unwrap($promises);

return new ResponseFactory()->createJsonResponse([
'users' => json_decode($results['users']->getBody()->getContents(), true),
'orders' => json_decode($results['orders']->getBody()->getContents(), true),
'stats' => json_decode($results['stats']->getBody()->getContents(), true),
]);
}
```

## File Uploads

```php
#[Route('/upload', methods: ['POST'])]
public function upload(GuzzleHttpClient $client): ResponseInterface
{
$response = $client->post('https://api.example.com/upload', [
'multipart' => [
[
'name' => 'file',
'contents' => fopen('/path/to/file.pdf', 'r'),
'filename' => 'document.pdf',
],
[
'name' => 'description',
'contents' => 'Important document',
],
],
]);

return new ResponseFactory()->createJsonResponse(['uploaded' => true]);
}
```

## PSR-18 Compatibility

The bridge implements PSR-18 `ClientInterface`, making it interchangeable with any PSR-18 client:

```php
use Psr\Http\Client\ClientInterface;

// Works with any PSR-18 compatible code
function fetchData(ClientInterface $client, RequestInterface $request): array
{
$response = $client->sendRequest($request);
return json_decode($response->getBody()->getContents(), true);
}
```

## Features

- **PSR-18 compatible** - Implements `ClientInterface`

- **Full Guzzle API** - Access all Guzzle features

- **Async requests** - Concurrent HTTP requests with promises

- **Middleware system** - Retry, logging, caching

- **File uploads** - Multipart form data support

- **Streaming** - Handle large responses efficiently

> **Info:** **Why Guzzle?** While Larafony includes a built-in PSR-18 HTTP client, Guzzle offers async/concurrent requests, a rich middleware ecosystem, automatic retries, and cookie jar management.
