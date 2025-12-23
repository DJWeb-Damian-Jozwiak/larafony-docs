---
title: "Configuration & Environment Variables"
description: "Manage application configuration with PHP files and environment variables using Larafony\'s built-in configuration system."
---

# Configuration & Environment Variables

> **Info:** **Best Practice:** Store sensitive data in `.env` files, never commit them to version control.

## Overview

Larafony's configuration system provides two layers:

- **Environment Variables (.env)** - Store sensitive data like API keys, database credentials

- **Configuration Files (config/*.php)** - Define structured application settings

## Environment Variables

### Creating .env File

Create a `.env` file in your project root:

```bash
# Application Configuration
APP_NAME=Larafony
APP_URL=https://larafony.local
APP_DEBUG=true

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=larafony
DB_USERNAME=root
DB_PASSWORD=secret

# Mail Configuration
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

### Environment File Features

The .env parser supports:

- **Comments** - Lines starting with `#`

- **Quoted values** - Single or double quotes for strings with spaces

- **Escape sequences** - `\n`, `\r`, `\t`, `\"`, `\\`

- **No overwriting** - Existing environment variables are never overwritten

```bash
# Comments are supported
APP_NAME="My Application" # Quoted values
APP_KEY='base64:...' # Single quotes work too

# Escape sequences
APP_MESSAGE="Line 1\nLine 2\tTabbed"

# No quotes needed for simple values
DEBUG=true
PORT=3000
```

### Reading Environment Variables

Use `EnvReader` to access environment variables with optional default values:

```php
use Larafony\Framework\Config\Environment\EnvReader;

// Read environment variable
$appName = EnvReader::read('APP_NAME');

// With default value
$debug = EnvReader::read('APP_DEBUG', false);
$port = EnvReader::read('PORT', 8000);
```

## Configuration Files

### Creating Config Files

Configuration files are stored in the `config/` directory and return PHP arrays:

```php
<?php
// config/app.php

declare(strict_types=1);

use Larafony\Framework\Config\Environment\EnvReader;

return [
'name' => EnvReader::read('APP_NAME', 'Larafony'),
'url' => EnvReader::read('APP_URL', 'http://localhost'),
'debug' => EnvReader::read('APP_DEBUG', false),
'timezone' => 'UTC',
'locale' => 'en',
];
```

```php
<?php
// config/database.php

declare(strict_types=1);

use Larafony\Framework\Config\Environment\EnvReader;

return [
'default' => EnvReader::read('DB_CONNECTION', 'mysql'),

'connections' => [
'mysql' => [
'driver' => 'mysql',
'host' => EnvReader::read('DB_HOST', '127.0.0.1'),
'port' => (int) EnvReader::read('DB_PORT', '3306'),
'database' => EnvReader::read('DB_DATABASE', 'larafony'),
'username' => EnvReader::read('DB_USERNAME', 'root'),
'password' => EnvReader::read('DB_PASSWORD', ''),
'charset' => 'utf8mb4',
],
],
];
```

### Accessing Configuration

Use the `Config` facade with dot notation to access configuration values:

```php
use Larafony\Framework\Web\Config;

// Get configuration value
$appName = Config::get('app.name');

// Get with default value
$timezone = Config::get('app.timezone', 'UTC');

// Get nested values using dot notation
$dbHost = Config::get('database.connections.mysql.host');

// Get entire array
$dbConfig = Config::get('database.connections.mysql');
```

### Setting Configuration at Runtime

```php
use Larafony\Framework\Web\Config;

// Set configuration value
Config::set('app.custom_setting', 'value');

// Set nested values
Config::set('cache.stores.redis.host', '127.0.0.1');
```

## Bootstrap Process

### How Configuration Loads

The `ConfigServiceProvider` automatically loads configuration during application bootstrap:

```php
// bootstrap/app.php
$app->withServiceProviders([
ConfigServiceProvider::class, // Loads .env and config files
// ... other providers
]);

// After bootstrap, configuration is available:
Config::get('app.name');
```

The loading process:

- Load `.env` file from project root

- Set environment variables in `$_ENV`, `$_SERVER`, and via `putenv()`

- Scan `config/` directory for PHP files

- Load each config file and store under its filename as key

## Environment-Specific Configuration

### Using .env.example

Create a `.env.example` file as a template for your team:

```bash
# .env.example (committed to git)
APP_NAME=
APP_URL=
APP_DEBUG=

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

Team members copy this to `.env` and fill in their values:

```bash
cp .env.example .env
# Edit .env with your local values
```

### .gitignore

Always add `.env` to your `.gitignore`:

```bash
# .gitignore
.env
.env.local
.env.*.local
```

## Practical Examples

### Example 1: Database Configuration

```php
// In your service or repository
use Larafony\Framework\Web\Config;

class DatabaseConnection
{
public function connect(): PDO
{
$config = Config::get('database.connections.mysql');

$dsn = sprintf(
'mysql:host=%s;port=%d;dbname=%s;charset=%s',
$config['host'],
$config['port'],
$config['database'],
$config['charset']
);

return new PDO(
$dsn,
$config['username'],
$config['password']
);
}
}
```

### Example 2: Feature Flags

```php
// config/features.php
return [
'new_dashboard' => EnvReader::read('FEATURE_NEW_DASHBOARD', false),
'api_v2' => EnvReader::read('FEATURE_API_V2', false),
'beta_features' => EnvReader::read('FEATURE_BETA', false),
];

// In your controller
if (Config::get('features.new_dashboard')) {
return $this->render('dashboard.new');
}

return $this->render('dashboard.classic');
```

### Example 3: Service Configuration

```php
// config/services.php
return [
'stripe' => [
'key' => EnvReader::read('STRIPE_KEY'),
'secret' => EnvReader::read('STRIPE_SECRET'),
],

'aws' => [
'key' => EnvReader::read('AWS_ACCESS_KEY_ID'),
'secret' => EnvReader::read('AWS_SECRET_ACCESS_KEY'),
'region' => EnvReader::read('AWS_DEFAULT_REGION', 'us-east-1'),
'bucket' => EnvReader::read('AWS_BUCKET'),
],
];

// Usage
use Larafony\Framework\Web\Config;

$stripeKey = Config::get('services.stripe.key');
$awsRegion = Config::get('services.aws.region');
```

## Best Practices

#### Do

- Store sensitive data in .env files

- Use EnvReader in config files to read environment variables

- Provide sensible default values

- Use dot notation for nested configuration

- Commit .env.example, never commit .env

- Type cast environment variables (int, bool) in config files

#### Don't

- Don't commit .env files to version control

- Don't store sensitive data in config files directly

- Don't use EnvReader directly in business logic (use Config instead)

- Don't forget to add .env to .gitignore

## Security Considerations

> **Danger:** **Security Warning:** Never store passwords, API keys, or secrets directly in configuration files. Always use environment variables via `.env` files.

- **Production Secrets** - Use environment variables set at the OS/container level

- **File Permissions** - Ensure .env files are not web-accessible

- **Version Control** - Never commit .env to git

- **Secret Rotation** - Regularly update sensitive credentials

## Next Steps

#### Database

Learn about Schema Builder and Query Builder for database operations.

[
Read Guide 
](/docs/schema-builder)

#### Logging

Set up PSR-3 compliant logging for your application.

[
Read Guide 
](/docs/logging)
