---
title: "Application Bootstrap"
description: "Configure your Larafony application with service providers and routes"
---

# Application Bootstrap

## The Bootstrap Process

Larafony applications bootstrap in two phases:

- **Application Creation** - Create the application instance

- **Service Registration** - Register service providers and configure routes

## bootstrap/app.php

This is the main bootstrap file for web applications:

```php
<?php

declare(strict_types=1);

use Larafony\Framework\Config\ServiceProviders\ConfigServiceProvider;
use Larafony\Framework\Database\ServiceProviders\DatabaseServiceProvider;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Larafony\Framework\Routing\ServiceProviders\RouteServiceProvider;
use Larafony\Framework\View\ServiceProviders\ViewServiceProvider;
use Larafony\Framework\Web\ServiceProviders\WebServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

// Create application instance
$app = \Larafony\Framework\Web\Application::instance(
base_path: dirname(__DIR__)
);

// Register service providers
$app->withServiceProviders([
ErrorHandlerServiceProvider::class,
ConfigServiceProvider::class,
DatabaseServiceProvider::class,
HttpServiceProvider::class,
RouteServiceProvider::class,
ViewServiceProvider::class,
WebServiceProvider::class,
]);

// Configure routes
$app->withRoutes(function ($router) {
$router->loadAttributeRoutes(__DIR__ . '/../src/Controllers');
});

return $app;
```

## Service Providers

Service providers register services in the dependency injection container.
Larafony includes several core service providers:

### ErrorHandlerServiceProvider

Registers error and exception handlers

### ConfigServiceProvider

Loads configuration files from the `config/` directory

### DatabaseServiceProvider

Registers database connection and query builder

### HttpServiceProvider

Registers PSR-7 HTTP message factories

### RouteServiceProvider

Registers the router and route matching

### ViewServiceProvider

Registers the Blade template engine

### WebServiceProvider

Registers web-specific services (controllers, middleware, etc.)

> **Info:** **Order Matters:** Service providers are registered in the order listed. Make sure dependencies are registered before services that use them.

## Route Configuration

The `withRoutes()` method configures how routes are discovered:

```php
$app->withRoutes(function ($router) {
// Auto-discover routes from controller attributes
$router->loadAttributeRoutes(__DIR__ . '/../src/Controllers');
});
```

This scans the `src/Controllers` directory and automatically registers
all routes defined with `#[Route]` attributes.

## Global Middleware

Register middleware that runs on every request:

```php
$app->withMiddleware([
\App\Middleware\LogRequestMiddleware::class,
\App\Middleware\CorsMiddleware::class,
]);
```

## bootstrap/console.php

For console applications, use a similar bootstrap file:

```php
<?php

declare(strict_types=1);

use Larafony\Framework\Console\Application as ConsoleApplication;

require_once __DIR__ . '/../vendor/autoload.php';

$app = ConsoleApplication::instance(
base_path: dirname(__DIR__)
);

// Register service providers (same as web)
$app->withServiceProviders([
ErrorHandlerServiceProvider::class,
ConfigServiceProvider::class,
DatabaseServiceProvider::class,
// Console doesn't need HttpServiceProvider or ViewServiceProvider
]);

// Register console commands
$app->withCommands(__DIR__ . '/../src/Console/Commands');

return $app;
```

## Running the Application

The `public/index.php` file is the entry point:

```php
<?php

declare(strict_types=1);

// Bootstrap the application
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Run the application
$app->run();
```

The `run()` method:

- Creates a PSR-7 request from PHP globals

- Routes the request to the appropriate controller

- Processes middleware

- Executes the controller

- Sends the response

## Custom Service Providers

Create your own service providers for custom services:

```php
<?php

namespace App\Providers;

use Larafony\Framework\DI\ServiceProvider;
use Psr\Container\ContainerInterface;

class AppServiceProvider extends ServiceProvider
{
public function register(ContainerInterface $container): void
{
// Register your services
$container->set(MyService::class, function () {
return new MyService();
});
}
}
```

Register it in `bootstrap/app.php`:

```php
$app->withServiceProviders([
// ... other providers
\App\Providers\AppServiceProvider::class,
]);
```

## Environment-Specific Bootstrap

Configure services differently based on environment:

```php
$app = Application::instance(base_path: dirname(__DIR__));

// Register base providers
$app->withServiceProviders([
ErrorHandlerServiceProvider::class,
ConfigServiceProvider::class,
DatabaseServiceProvider::class,
]);

// Environment-specific configuration
if (env('APP_ENV') === 'production') {
// Production-only services
$app->withServiceProviders([
CacheServiceProvider::class,
]);
} else {
// Development-only services
$app->withServiceProviders([
DebugServiceProvider::class,
]);
}

// Continue with common providers
$app->withServiceProviders([
HttpServiceProvider::class,
RouteServiceProvider::class,
ViewServiceProvider::class,
WebServiceProvider::class,
]);

return $app;
```

## Configuration Files

Service providers often load configuration from `config/` files:

### config/database.php

```php
<?php

return [
'default' => env('DB_CONNECTION', 'mysql'),

'connections' => [
'mysql' => [
'driver' => 'mysql',
'host' => env('DB_HOST', '127.0.0.1'),
'port' => env('DB_PORT', '3306'),
'database' => env('DB_DATABASE', 'larafony'),
'username' => env('DB_USERNAME', 'root'),
'password' => env('DB_PASSWORD', ''),
'charset' => 'utf8mb4',
],
],
];
```

### config/view.php

```php
<?php

return [
'paths' => [
__DIR__ . '/../resources/views',
],

'cache' => [
'enabled' => env('VIEW_CACHE_ENABLED', true),
'path' => __DIR__ . '/../storage/cache/views',
],

'blade' => [
'directives' => [
// Custom Blade directives
],
],
];
```

> **Success:** **Tip:** Use the `env()` helper to read from `.env` files, and always provide sensible defaults as the second parameter.

## Testing Bootstrap

For tests, create a minimal bootstrap:

```php
// tests/bootstrap.php
$app = Application::instance(base_path: dirname(__DIR__));

$app->withServiceProviders([
// Only providers needed for testing
ConfigServiceProvider::class,
DatabaseServiceProvider::class,
]);

return $app;
```

## Next Steps

- [Learn about project structure →](/docs/structure)

- [Learn about global middleware →](/docs/middleware)

- [Learn about controllers →](/docs/controllers)
