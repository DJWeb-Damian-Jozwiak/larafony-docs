---
title: "PHP DebugBar Bridge"
description: "Integrate PHP DebugBar for visual in-browser debugging."
---

# PHP DebugBar Bridge

## Installation

```bash
composer require larafony/debugbar-php
```

## Configuration

```php
use Larafony\DebugBar\Php\ServiceProviders\PhpDebugBarServiceProvider;

$app->withServiceProviders([
PhpDebugBarServiceProvider::class
]);
```

## Usage

The debug bar appears automatically at the bottom of pages in development mode. It shows:

- **Messages** - Log messages and debug output

- **Timeline** - Request duration and performance

- **Exceptions** - Error details and stack traces

- **Database** - SQL queries and execution time

- **Request** - Headers, parameters, session data

- **Memory** - Peak memory usage

## Adding Messages

```php
use Larafony\DebugBar\Php\DebugBar;

$debugbar = $container->get(DebugBar::class);

$debugbar->info('User logged in');
$debugbar->warning('Cache miss for key: users');
$debugbar->error('Payment failed');

// With context
$debugbar->debug('Query result', ['count' => 42]);
```

## Features

- **Visual debugging** - In-browser toolbar

- **Query monitoring** - All SQL queries with timing

- **Performance metrics** - Memory, time, timeline

- **Request inspection** - Headers, POST, GET, cookies

- **Exception display** - Full stack traces

> **Warning:** **Production Warning:** Disable the debug bar in production to prevent exposing sensitive information. Set `APP_DEBUG=false` in your `.env` file.
