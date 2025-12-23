---
title: "HTTP Client (PSR-18)"
description: "Make HTTP requests to external APIs with Larafony\'s PSR-18 compliant HTTP client."
---

# HTTP Client (PSR-18)

> **Info:** **PSR-18 Compliant:** Fully implements `Psr\Http\Client\ClientInterface` for maximum interoperability.

## Overview

The HTTP Client provides a clean API for making outbound HTTP requests:

- **PSR-18 Compliant** - Standard HTTP client interface

- **Native CurlHandle** - No external dependencies

- **Configurable** - Timeouts, SSL, proxy, redirects

- **Testable** - MockHttpClient for testing

## Basic Usage

### GET Request

```php
use Larafony\Framework\Http\Client\CurlHttpClient;
use Larafony\Framework\Http\Factories\RequestFactory;

$client = new CurlHttpClient();
$requestFactory = new RequestFactory();

// Create GET request
$request = $requestFactory->createRequest('GET', 'https://api.github.com/users/octocat');

// Send request
$response = $client->sendRequest($request);

// Get response data
echo $response->getStatusCode(); // 200
echo $response->getBody(); // JSON response
```

### POST Request with JSON

```php
use Larafony\Framework\Http\Factories\StreamFactory;

$streamFactory = new StreamFactory();

// Create POST request
$request = $requestFactory->createRequest('POST', 'https://api.example.com/users')
 ->withHeader('Content-Type', 'application/json')
 ->withBody($streamFactory->createStream(json_encode([
'name' => 'John Doe',
'email' => 'john@example.com'
])));

$response = $client->sendRequest($request);
$data = json_decode($response->getBody(), true);
```

## Configuration

### Client Configuration

```php
use Larafony\Framework\Http\Client\Config\HttpClientConfig;

// Custom configuration
$config = new HttpClientConfig(
timeout: 30, // Request timeout in seconds
followRedirects: true, // Follow HTTP redirects
maxRedirects: 5, // Maximum redirects to follow
verifyPeer: true, // Verify SSL certificate
verifyHost: true, // Verify SSL host
);

$client = new CurlHttpClient($config);
```

### Convenience Factory Methods

```php
// Quick configurations
$config = HttpClientConfig::withTimeout(60);
$config = HttpClientConfig::insecure(); // Disable SSL verification (dev only!)
$config = HttpClientConfig::withProxy('proxy.local:8080', 'user:pass');
```

## Making Requests

### Different HTTP Methods

```php
// GET
$request = $requestFactory->createRequest('GET', 'https://api.example.com/users');

// POST
$request = $requestFactory->createRequest('POST', 'https://api.example.com/users');

// PUT
$request = $requestFactory->createRequest('PUT', 'https://api.example.com/users/1');

// PATCH
$request = $requestFactory->createRequest('PATCH', 'https://api.example.com/users/1');

// DELETE
$request = $requestFactory->createRequest('DELETE', 'https://api.example.com/users/1');
```

### Adding Headers

```php
$request = $requestFactory->createRequest('GET', 'https://api.example.com/data')
 ->withHeader('Authorization', 'Bearer ' . $token)
 ->withHeader('Accept', 'application/json')
 ->withHeader('User-Agent', 'LarafonyApp/1.0');
```

### Query Parameters

```php
// Build URL with query parameters
$url = 'https://api.example.com/search?' . http_build_query([
'q' => 'larafony',
'page' => 1,
'per_page' => 20
]);

$request = $requestFactory->createRequest('GET', $url);
```

## Response Handling

### Reading Response

```php
$response = $client->sendRequest($request);

// Status code
$statusCode = $response->getStatusCode(); // 200

// Headers
$contentType = $response->getHeaderLine('Content-Type');
$allHeaders = $response->getHeaders();

// Body
$body = $response->getBody()->getContents();

// JSON response
$data = json_decode($body, true);
```

### Checking Status

```php
if ($response->getStatusCode() === 200) {
// Success
}

if ($response->getStatusCode() >= 400) {
// Error
}
```

## Error Handling

### Network Errors

```php
use Larafony\Framework\Http\Client\Exceptions\ClientError;
use Larafony\Framework\Http\Client\Exceptions\ConnectionError;
use Larafony\Framework\Http\Client\Exceptions\TimeoutError;

try {
$response = $client->sendRequest($request);
} catch (TimeoutError $e) {
// Request timed out
echo "Request timed out";
} catch (ConnectionError $e) {
// Connection failed
echo "Connection failed: " . $e->getMessage();
} catch (ClientError $e) {
// Other client errors
echo "Client error: " . $e->getMessage();
}
```

### HTTP Status Errors

```php
use Larafony\Framework\Http\Client\Exceptions\NotFoundError;
use Larafony\Framework\Http\Client\Exceptions\UnauthorizedError;

try {
$response = $client->sendRequest($request);
} catch (UnauthorizedError $e) {
// 401 Unauthorized
echo "Authentication required";
} catch (NotFoundError $e) {
// 404 Not Found
echo "Resource not found";
}
```

## Testing with MockHttpClient

### Creating Mock Responses

```php
use Larafony\Framework\Http\Client\MockHttpClient;
use Larafony\Framework\Http\Factories\ResponseFactory;

// Create mock response
$mockResponse = (new ResponseFactory())
 ->createResponse(200)
 ->withHeader('Content-Type', 'application/json')
 ->withJson(['id' => 1, 'name' => 'Test User']);

// Create mock client
$client = new MockHttpClient($mockResponse);

// Use in tests - no actual HTTP request is made
$response = $client->sendRequest($anyRequest);
// Always returns the mocked response
```

### Testing Service with HTTP Client

```php
class GitHubService
{
public function __construct(
private readonly ClientInterface $httpClient,
private readonly RequestFactory $requestFactory
) {}

public function getUser(string $username): array
{
$request = $this->requestFactory->createRequest(
'GET',
"https://api.github.com/users/{$username}"
);

$response = $this->httpClient->sendRequest($request);
return json_decode($response->getBody(), true);
}
}

// In tests
$mockResponse = (new ResponseFactory())
 ->createResponse(200)
 ->withJson(['login' => 'octocat', 'name' => 'The Octocat']);

$mockClient = new MockHttpClient($mockResponse);
$service = new GitHubService($mockClient, new RequestFactory());

$user = $service->getUser('octocat'); // Uses mock, no network call
assert($user['login'] === 'octocat');
```

## Practical Examples

### Example 1: API Client

```php
class WeatherApiClient
{
public function __construct(
private readonly ClientInterface $httpClient,
private readonly string $apiKey
) {}

public function getCurrentWeather(string $city): array
{
$url = 'https://api.weather.com/current?' . http_build_query([
'city' => $city,
'apikey' => $this->apiKey
]);

$request = (new RequestFactory())
->createRequest('GET', $url)
->withHeader('Accept', 'application/json');

try {
$response = $this->httpClient->sendRequest($request);
return json_decode($response->getBody(), true);
} catch (ClientError $e) {
throw new WeatherApiException('Failed to fetch weather', 0, $e);
}
}
}
```

### Example 2: Webhook Sender

```php
class WebhookService
{
public function __construct(
private readonly ClientInterface $httpClient
) {}

public function sendWebhook(string $url, array $data): bool
{
$request = (new RequestFactory())
->createRequest('POST', $url)
->withHeader('Content-Type', 'application/json')
->withBody(
(new StreamFactory())->createStream(json_encode($data))
);

try {
$response = $this->httpClient->sendRequest($request);
return $response->getStatusCode() === 200;
} catch (ClientError $e) {
Log::error('Webhook failed', [
'url' => $url,
'error' => $e->getMessage()
]);
return false;
}
}
}
```

## Best Practices

#### Do

- Use dependency injection for HTTP client

- Set appropriate timeouts for external APIs

- Handle network errors gracefully

- Use MockHttpClient in tests

- Add retry logic for transient failures

- Log failed requests for debugging

#### Don't

- Don't disable SSL verification in production

- Don't ignore network errors

- Don't use infinite timeouts

- Don't make HTTP calls in loops without rate limiting

## Next Steps

#### Logging

Log HTTP requests and responses for debugging.

[
Read Guide 
](/docs/logging)

#### Configuration

Store API keys and endpoints in configuration.

[
Read Guide 
](/docs/config)
