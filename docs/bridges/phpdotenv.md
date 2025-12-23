---
title: "PHP dotenv Bridge"
description: "Load environment variables from .env files using vlucas/phpdotenv."
---

# PHP dotenv Bridge

## Installation

```bash
composer require larafony/env-phpdotenv
```

## Configuration

```php
use Larafony\Env\Phpdotenv\ServiceProviders\PhpdotenvServiceProvider;

$app->withServiceProviders([
PhpdotenvServiceProvider::class
]);
```

## Features

- **Variable expansion** - `${BASE_URL}/api` expands correctly

- **Multiline values** - Support for multiline strings

- **Comments** - Lines starting with `#` are ignored

- **Type casting** - Boolean and null values

- **Validation** - Required variables enforcement

## Example .env

```bash
# Application
APP_NAME="My Larafony App"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

# Database
DB_HOST=localhost
DB_DATABASE=larafony
DB_USERNAME=root
DB_PASSWORD=secret

# Variable expansion
API_URL="${APP_URL}/api/v1"

# Multiline value
RSA_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEA...
-----END RSA PRIVATE KEY-----"
```
