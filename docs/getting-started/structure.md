---
title: "Project Structure"
description: "Understanding how Larafony projects are organized"
---

# Project Structure

## Directory Structure

Larafony follows a clean, intuitive structure inspired by modern PHP frameworks.
Here's what each directory contains:

```bash
my-app/
├── bootstrap/ # Application bootstrap files
│ ├── app.php # Web application bootstrap
│ └── console.php # Console application bootstrap
├── config/ # Configuration files
│ ├── database.php # Database configuration
│ └── view.php # View engine configuration
├── database/
│ ├── migrations/ # Database migrations
│ └── seeders/ # Database seeders
├── public/
│ └── index.php # Application entry point
├── resources/
│ └── views/ # Blade templates
├── src/
│ ├── Controllers/ # HTTP controllers
│ ├── Models/ # ORM models
│ ├── DTOs/ # Data Transfer Objects
│ ├── Middleware/ # HTTP middleware
│ ├── Console/ # Console commands
│ └── View/ # View components
├── storage/
│ ├── cache/ # Compiled views & cache
│ └── logs/ # Application logs
├── .env # Environment variables
├── .env.example # Example environment file
└── composer.json # Dependencies
```

## Bootstrap Directory

The `bootstrap/` directory contains files that bootstrap your application.

### bootstrap/app.php

This file creates and configures the web application instance. It registers service providers
and sets up route discovery:

```php
<?php

use Larafony\Framework\Web\Application;

$app = Application::instance(base_path: dirname(__DIR__));

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

// Auto-discover routes from controller attributes
$app->withRoutes(function ($router) {
$router->loadAttributeRoutes(__DIR__ . '/../src/Controllers');
});

return $app;
```

### bootstrap/console.php

Similar to `app.php`, but for console commands. It bootstraps the console application
and registers command directories.

## Config Directory

Configuration files live in `config/`. These files return arrays of configuration options.

### config/database.php

Database connection settings:

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
'collation' => 'utf8mb4_unicode_ci',
],
],
];
```

## Public Directory

The `public/` directory contains the front controller (`index.php`) and assets.
This is your document root—all requests go through `index.php`.

```php
<?php

require_once __DIR__ . '/../bootstrap/app.php';

$app->run();
```

## Src Directory

Your application code lives in `src/`. This follows the PSR-4 autoloading standard
with the `App\` namespace.

### Controllers

HTTP controllers with attribute-based routing. See the [Controllers & Routing](/docs/controllers) guide.

### Models

ORM models with attribute-based relationships. See the [Models & Relationships](/docs/models) guide.

### DTOs

Data Transfer Objects for validation. See the [DTO Validation](/docs/validation) guide.

### Middleware

PSR-15 middleware classes. See the [Middleware](/docs/middleware) guide.

## Storage Directory

The `storage/` directory contains compiled Blade templates, file caches, and logs.
Make sure this directory is writable.

> **Warning:** **Permissions:** The `storage/` directory must be writable by your web server.

## Environment Configuration

The `.env` file contains environment-specific configuration. Never commit this file to version control.

```bash
APP_NAME=Larafony
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=larafony
DB_USERNAME=root
DB_PASSWORD=

VIEW_CACHE_ENABLED=true
```

> **Info:** **Tip:** Copy `.env.example` to `.env` and customize it for your environment.

## Next Steps

- [Learn about Models & Relationships →](/docs/models)

- [Learn about Controllers & Routing →](/docs/controllers)

- [Learn about Application Bootstrap →](/docs/bootstrap)
