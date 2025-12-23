---
title: "Error Handling"
description: "Comprehensive error handling system with beautiful debug views and production-ready error pages."
---

# Error Handling

PSR-3 Ready
Blade Templates
Debug Mode
Production Safe

## Overview

The Larafony error handling system captures all uncaught exceptions and PHP errors, rendering them through
beautiful Blade templates. It automatically switches between detailed debug views for development and
clean, user-friendly error pages for production based on the `APP_DEBUG` environment variable.

## Key Features

- **Native PHP Exception Handlers** - Uses `set_exception_handler()` and `set_error_handler()` for reliable error capture

- **Interactive Debug View** - VS Code-inspired dark theme with clickable stack frames and code snippets

- **Environment Awareness** - Automatically detects `APP_DEBUG` to show appropriate views

- **Status Code Detection** - Distinguishes between 404 (NotFoundError) and 500 (other exceptions)

- **Graceful Fallback** - Provides plain HTML if Blade rendering fails

- **Fatal Error Handling** - Catches fatal errors through shutdown functions

## Configuration

### Environment Setup

Control error display mode through your `.env` file:

```bash
# Development: Show detailed debug traces
APP_DEBUG=true

# Production: Show user-friendly error pages
APP_DEBUG=false
```

### Service Provider Registration

Register the ErrorHandlerServiceProvider in your `bootstrap/app.php`.
**Important:** It must be registered AFTER ViewServiceProvider because it depends on ViewManager.

```php
$app->withServiceProviders([
ConfigServiceProvider::class,
DatabaseServiceProvider::class,
HttpServiceProvider::class,
RouteServiceProvider::class,
ViewServiceProvider::class, // ViewManager must be registered first
WebServiceProvider::class,
ErrorHandlerServiceProvider::class, // Must be LAST
]);
```

## Error Views

Create three Blade views in `resources/views/blade/errors/`:

### 404 Error Page (errors.404)

Rendered when `NotFoundError` exception is thrown:

```html
<!DOCTYPE html>
<html lang="en">
<head>
<title>404 - Page Not Found</title>
<style>
body {
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
/* Beautiful purple gradient with animated stars */
}

<body>
<h1>404 - Page Not Found</h1>

The page you're looking for doesn't exist.

[Go Home](/)

```

### 500 Error Page (errors.500)

Rendered for all other exceptions in production mode:

```html
<!DOCTYPE html>
<html lang="en">
<head>
<title>500 - Internal Server Error</title>
<style>
body {
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
/* Matching blue gradient theme */
}

<body>
<h1>500 - Internal Server Error</h1>

Something went wrong on our end.

[Go Home](/)

```

### Debug View (errors.debug)

Rendered in debug mode with full exception details and interactive backtrace:

```php
<!DOCTYPE html>
<html lang="en">
<head>
<title>{{ $exception['class'] }} | Debug</title>

<body>
<!-- Exception details -->

{{ $exception['class'] }}

{{ $exception['message'] }}

{{ $exception['file'] }}:{{ $exception['line'] }}

<!-- Sidebar with clickable stack frames -->

@foreach($backtrace as $index => $frame)

{{ $frame['class'] }}::{{ $frame['function'] }}()

{{ $frame['file'] }}:{{ $frame['line'] }}

@endforeach

<!-- Code snippets with line numbers -->

@foreach($backtrace as $index => $frame)

@foreach($frame['snippet']['lines'] as $lineNum => $lineContent)

{{ $lineNum }}
{{ $lineContent }}

@endforeach

@endforeach

<!-- JavaScript for frame navigation -->
<script>
document.querySelectorAll('.frame').forEach(frame => {
frame.addEventListener('click', function() {
// Switch to selected frame's code
});
});

</html>
```

## Usage Examples

### Throwing 404 Errors

Throw `NotFoundError` to trigger a 404 response:

```php
use Larafony\Framework\Core\Exceptions\NotFoundError;

public function findForRoute(string|int $value): static
{
$result = self::query()->where('id', '=', $value)->first();

if ($result === null) {
throw new NotFoundError(
sprintf('Model %s with id %s not found', static::class, $value)
);
}

return $result;
}
```

### Custom Exception Handling

Create custom exceptions extending NotFoundError:

```php
namespace App\Exceptions;

use Larafony\Framework\Core\Exceptions\NotFoundError;

class ResourceNotFoundException extends NotFoundError
{
public function __construct(string $resource, string|int $id)
{
parent::__construct(
sprintf('Resource "%s" with ID "%s" was not found', $resource, $id)
);
}
}

// Usage
throw new ResourceNotFoundException('User', 42);
```

### Programmatic Backtrace Generation

Generate backtraces programmatically for logging or debugging:

```php
use Larafony\Framework\ErrorHandler\Backtrace;

$backtrace = new Backtrace();

try {
// Risky operation
throw new \RuntimeException('Something went wrong');
} catch (\Throwable $e) {
$trace = $backtrace->generate($e);

foreach ($trace->frames as $frame) {
echo $frame->file . ':' . $frame->line . PHP_EOL;
echo $frame->class . '::' . $frame->function . '()' . PHP_EOL;

// Access code snippet
foreach ($frame->snippet->lines as $lineNum => $lineContent) {
echo $lineNum . ': ' . $lineContent . PHP_EOL;
}
}
}
```

## Architecture

### Core Components

- `DetailedErrorHandler` - Main error handler implementing ErrorHandler contract

- `Backtrace` - Factory for generating TraceCollection from exceptions

- `TraceCollection` - Immutable collection of TraceFrame objects

- `TraceFrame` - Readonly value object representing a stack frame

- `CodeSnippet` - Extracts code context around error lines

- `ErrorHandlerServiceProvider` - Integrates error handler into service container

### Modern PHP Features

The error handling system leverages PHP 8.5 features:

```php
// Property hooks for immutable public access
class TraceCollection
{
public private(set) array $frames; // Public read, private write
}

// Readonly value objects
readonly class TraceFrame
{
public function __construct(
public string $file,
public int $line,
public ?string $class,
public string $function,
public array $args,
public CodeSnippet $snippet
) {}
}
```

## Comparison with Other Frameworks

<table class="table table-dark table-bordered mt-4">
<thead>
<tr>
<th>Feature</th>
<th>Larafony</th>
<th>Laravel</th>
<th>Symfony
</thead>
<tbody>
<tr>
<td>**Registration**</td>
<td>`set_exception_handler()`</td>
<td>`withExceptions()` in bootstrap</td>
<td>Event listener on `kernel.exception`
<tr>
<td>**Debug Views**</td>
<td>Custom Blade templates</td>
<td>Whoops library</td>
<td>Symfony Profiler
<tr>
<td>**Configuration**</td>
<td>ENV (`APP_DEBUG`)</td>
<td>Config files + ENV</td>
<td>YAML/PHP config
<tr>
<td>**Approach**</td>
<td>Native PHP handlers + Blade</td>
<td>Laravel exceptions + middleware</td>
<td>Event-driven architecture

**Key Difference:** Larafony uses PHP's native exception handlers directly for simplicity and transparency,
while Laravel and Symfony use framework-specific abstractions. This makes Larafony's error handling easier to understand
and customize without deep framework knowledge.

## Learn More

This implementation is explained in detail with step-by-step tutorials, tests, and best practices at
[
**masterphp.eu**
](https://masterphp.eu)

**Demo App:** See error handling in action with custom 404/500 pages and interactive debug views. The demo application showcases production-ready error templates with beautiful gradients and full exception backtraces in development mode.

[
View on Packagist
](https://packagist.org/packages/larafony/skeleton)
[
View on GitHub
](https://github.com/DJWeb-Damian-Jozwiak/larafony-demo-app)
