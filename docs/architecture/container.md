---
title: "Dependency Injection Container (PSR-11)"
description: "Larafony includes a powerful PSR-11 compliant dependency injection container with automatic autowiring."
---

# Dependency Injection Container (PSR-11)

> **Info:** **PSR-11 Compliance:** The container follows `Psr\Container\ContainerInterface` standard for maximum interoperability.

## What is a Container?

The dependency injection container is the heart of Larafony. It manages class dependencies and performs automatic
dependency injection through constructor parameters. The container can:

- **Autowire dependencies** - Automatically resolve constructor dependencies

- **Bind values** - Store configuration values (strings, numbers, booleans)

- **Register services** - Store service instances or class names

- **Dot notation access** - Access nested values using dot syntax

## Basic Usage

### Accessing the Container

The container is available throughout your application. You can access it in several ways:

```php
use Larafony\Framework\Web\Application;
use Psr\Container\ContainerInterface;

// Method 1: Through Application instance
$app = Application::instance();
$container = $app; // Application implements ContainerInterface

// Method 2: Dependency injection (preferred)
class UserController
{
public function __construct(
private readonly ContainerInterface $container
) {}
}
```

### Retrieving Services

The `get()` method retrieves services from the container. If the service doesn't exist,
the container will attempt to autowire it:

```php
use App\Services\EmailService;

// Get a service - will autowire if not registered
$emailService = $container->get(EmailService::class);

// Get with dot notation
$dbHost = $container->get('database.host');
```

### Checking Service Existence

```php
if ($container->has(EmailService::class)) {
$service = $container->get(EmailService::class);
}
```

## Binding Values

### Simple Value Bindings

Use `bind()` to store scalar values (strings, numbers, booleans, null):

```php
// Bind configuration values
$container->bind('base_path', '/var/www/app');
$container->bind('debug', true);
$container->bind('timeout', 30);

// Retrieve bindings
$basePath = $container->getBinding('base_path'); // '/var/www/app'
$debug = $container->getBinding('debug'); // true
```

> **Warning:** **Note:** `bind()` only accepts scalar values. For objects or arrays, use `set()`.

## Registering Services

### Using set()

The `set()` method registers services, instances, or any values:

```php
use App\Services\EmailService;
use Larafony\Framework\Config\Contracts\ConfigContract;

// Set an instance
$config = new ConfigBase($container);
$container->set(ConfigContract::class, $config);

// Set a class name (will be autowired when retrieved)
$container->set('email', EmailService::class);

// Set with dot notation for nested values
$container->set('database.host', 'localhost');
$container->set('database.port', 3306);
```

## Automatic Autowiring

### How Autowiring Works

The container automatically resolves class dependencies by analyzing constructor parameters:

```php
namespace App\Services;

use App\Repositories\UserRepository;
use Psr\Log\LoggerInterface;

class UserService
{
public function __construct(
private readonly UserRepository $repository,
private readonly LoggerInterface $logger
) {}
}

// Container automatically resolves dependencies
$userService = $container->get(UserService::class);
// Container will:
// 1. Resolve UserRepository (and its dependencies)
// 2. Resolve LoggerInterface (must be registered)
// 3. Instantiate UserService with both dependencies
```

### Requirements for Autowiring

- Constructor parameters must be type-hinted with classes or interfaces

- Concrete classes are autowired automatically

- Interfaces must be registered in the container first

- Scalar parameters (string, int, etc.) cannot be autowired

## Service Providers

### Registering Services in Providers

Service providers are the recommended way to register services. They keep your bootstrap code organized:

```php
namespace App\Providers;

use Larafony\Framework\Container\ServiceProvider;
use Psr\Log\LoggerInterface;
use App\Services\FileLogger;

class LoggerServiceProvider extends ServiceProvider
{
public function register(): void
{
// Bind interface to implementation
$this->container->set(
LoggerInterface::class,
new FileLogger('/var/log/app.log')
);
}
}
```

Register the provider in your `bootstrap/app.php`:

```php
$app->registerProviders([
App\Providers\LoggerServiceProvider::class,
// ... other providers
]);
```

## Practical Examples

### Example: Repository Pattern

```php
// Interface
interface UserRepositoryInterface
{
public function find(int $id): ?User;
}

// Implementation
class DatabaseUserRepository implements UserRepositoryInterface
{
public function __construct(
private readonly ConnectionContract $db
) {}

public function find(int $id): ?User
{
// Database query logic
}
}

// Register in provider
class RepositoryServiceProvider extends ServiceProvider
{
public function register(): void
{
$this->container->set(
UserRepositoryInterface::class,
DatabaseUserRepository::class // Will be autowired
);
}
}

// Use in service
class UserService
{
public function __construct(
private readonly UserRepositoryInterface $repository
) {}
}
```

## Best Practices

#### Do

- Use type-hinted constructor injection

- Register interfaces in service providers

- Keep services focused and single-purpose

- Use dot notation for configuration values

- Leverage autowiring for concrete classes

#### Don't

- Don't use the container as a service locator

- Don't register services directly in controllers

- Don't use constructor injection for optional dependencies

- Don't create circular dependencies

## API Reference

<table class="table table-dark table-bordered">
<thead>
<tr>
<th>Method</th>
<th>Description</th>
<th>Returns
</thead>
<tbody>
<tr>
<td>`get(string $id)`</td>
<td>Retrieve a service or autowire it</td>
<td>`mixed`
<tr>
<td>`has(string $id)`</td>
<td>Check if service is registered</td>
<td>`bool`
<tr>
<td>`set(string $key, mixed $value)`</td>
<td>Register a service or value</td>
<td>`self`
<tr>
<td>`bind(string $key, scalar $value)`</td>
<td>Bind a scalar value</td>
<td>`void`
<tr>
<td>`getBinding(string $key)`</td>
<td>Retrieve a bound scalar value</td>
<td>`scalar`

## Next Steps

#### Configuration

Learn how to manage configuration files and environment variables.

[
Read Guide 
](/docs/config)

#### Database

Explore the Query Builder and Schema Builder for database operations.

[
Read Guide 
](/docs/query-builder)
