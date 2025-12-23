---
title: "Middleware"
description: "Create PSR-15 compliant middleware to process requests and responses"
---

# Middleware

## What is Middleware?

Middleware provides a convenient mechanism for filtering HTTP requests entering your application.
Larafony uses PSR-15 middleware, making it compatible with any PSR-15 compliant middleware.

## Creating Middleware

Implement the `MiddlewareInterface` from PSR-15:

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LogRequestMiddleware implements MiddlewareInterface
{
public function process(
ServerRequestInterface $request,
RequestHandlerInterface $handler
): ResponseInterface {
// Before request
$start = microtime(true);

// Process request
$response = $handler->handle($request);

// After request
$duration = microtime(true) - $start;
error_log("Request to {$request->getUri()} took {$duration}s");

return $response;
}
}
```

## Attaching Middleware to Routes

Use the `#[Middleware]` attribute to attach middleware to specific routes:

```php
<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use Larafony\Framework\Routing\Advanced\Attributes\{Route, Middleware};
use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;

class AdminController extends Controller
{
#[Route('/admin/dashboard', 'GET')]
#[Middleware(AuthMiddleware::class)]
public function dashboard(): ResponseInterface
{
// Only accessible if AuthMiddleware passes

return $this->render('admin.dashboard');
}
}
```

## Authentication Middleware Example

Here's a complete authentication middleware:

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Larafony\Framework\Http\Response\RedirectResponse;

class AuthMiddleware implements MiddlewareInterface
{
public function process(
ServerRequestInterface $request,
RequestHandlerInterface $handler
): ResponseInterface {
// Check if user is authenticated
$session = $request->getAttribute('session');

if (!$session || !$session->has('user_id')) {
// Not authenticated - redirect to login
return new RedirectResponse('/login');
}

// Authenticated - continue
return $handler->handle($request);
}
}
```

## CORS Middleware Example

Add CORS headers to API responses:

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsMiddleware implements MiddlewareInterface
{
public function process(
ServerRequestInterface $request,
RequestHandlerInterface $handler
): ResponseInterface {
// Handle preflight requests
if ($request->getMethod() === 'OPTIONS') {
return new Response(200, [
'Access-Control-Allow-Origin' => '*',
'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
]);
}

// Process request
$response = $handler->handle($request);

// Add CORS headers
return $response
->withHeader('Access-Control-Allow-Origin', '*')
->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
}
}
```

## Request Transformation

Middleware can modify the request before it reaches the controller:

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AddUserToRequestMiddleware implements MiddlewareInterface
{
public function process(
ServerRequestInterface $request,
RequestHandlerInterface $handler
): ResponseInterface {
$session = $request->getAttribute('session');

if ($session && $session->has('user_id')) {
// Load user from database
$user = User::query()->find($session->get('user_id'));

// Add user to request attributes
$request = $request->withAttribute('user', $user);
}

return $handler->handle($request);
}
}
```

Access the user in your controller:

```php
#[Route('/profile', 'GET')]
#[Middleware(AddUserToRequestMiddleware::class)]
public function profile(ServerRequestInterface $request): ResponseInterface
{
$user = $request->getAttribute('user');

return $this->render('profile', ['user' => $user]);
}
```

## Multiple Middleware

Stack multiple middleware on a single route:

```php
#[Route('/admin/users', 'GET')]
#[Middleware(AuthMiddleware::class)]
#[Middleware(AdminMiddleware::class)]
#[Middleware(LogRequestMiddleware::class)]
public function index(): ResponseInterface
{
// Protected by three middleware layers

return $this->render('admin.users');
}
```

> **Info:** **Execution Order:** Middleware executes in the order listed. In this example: Auth → Admin → LogRequest → Controller → LogRequest → Admin → Auth

## JSON API Middleware

Ensure all responses are JSON:

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonResponseMiddleware implements MiddlewareInterface
{
public function process(
ServerRequestInterface $request,
RequestHandlerInterface $handler
): ResponseInterface {
$response = $handler->handle($request);

// Ensure Content-Type is application/json
if (!$response->hasHeader('Content-Type')) {
$response = $response->withHeader('Content-Type', 'application/json');
}

return $response;
}
}
```

## Global Middleware

Register middleware globally in `bootstrap/app.php`:

```php
$app->withMiddleware([
LogRequestMiddleware::class,
CorsMiddleware::class,
]);
```

> **Success:** **Tip:** Use global middleware for cross-cutting concerns like logging and CORS. Use route-specific middleware for authorization and role checks.

## Next Steps

- [Learn about using middleware with controllers →](/docs/controllers)

- [Learn about configuring global middleware →](/docs/bootstrap)
