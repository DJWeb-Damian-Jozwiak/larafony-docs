---
title: "Console Error Debugging"
description: "Interactive, REPL-like debugging experience when exceptions occur in CLI commands"
---

# Console Error Debugging

#### 
🎯 Probably the Only One!

**The only PHP framework with native, built-from-scratch interactive error debugging in the console!**

While other frameworks like Laravel rely on external tools like PsySH for interactive console debugging,
Larafony implements its own sophisticated debug session system. When an exception occurs in your console commands,
you don't just get a stack trace and exit—you get an **interactive REPL-like experience** where you can
explore frames, inspect variables, view source code, and understand the error context without leaving your terminal
or installing external dependencies.

This is the same beautiful, web-like debugging experience you get in browsers... **but available in your console! 😎**

Native Implementation
Interactive Session
Frame Inspection
Variable Explorer
Source Viewer

## What Makes This Unique?

**🚀 Native, Built-in Implementation**

Unlike other frameworks that rely on external packages like PsySH, Larafony's console debugger is
built from scratch and deeply integrated into the framework. Zero external dependencies, zero configuration,
just pure framework magic!

## Key Features

- **Interactive Debug Session** - Drop into a REPL-like interface when exceptions occur

- **Command-Based Exploration** - Type commands to explore stack traces, frames, variables, and source code

- **Automatic Environment Detection** - Detects CLI vs web mode via `php_sapi_name()`

- **Beautiful Console Output** - Color-coded, syntax-highlighted display

- **Frame Navigation** - Jump between stack frames to understand execution flow

- **Variable Inspection** - View all variables available in each frame

- **Source Code Display** - See code snippets with context around errors

- **Fatal Error Recovery** - Catches shutdown errors (E_ERROR, E_PARSE, E_CORE_ERROR, etc.)

## How It Works

### Automatic Registration

The ErrorHandlerServiceProvider automatically detects if your application is running in CLI mode
and registers the appropriate handler. No configuration needed!

```php
// In ErrorHandlerServiceProvider::register()
$isConsole = php_sapi_name() === 'cli';

if ($isConsole) {
// Register console error handler with factory
$factory = new ConsoleRendererFactory($container);
$renderer = $factory->create();

$handler = new ConsoleHandler(
$renderer,
fn(int $exitCode) => exit($exitCode)
);

$container->set(BaseHandler::class, $handler);
} else {
// Register web error handler
$handler = new DetailedErrorHandler($viewManager, $debug);
$container->set(BaseHandler::class, $handler);
}
```

### Service Provider Order

**⚠️ Important:**
ConsoleServiceProvider must be registered **before** ErrorHandlerServiceProvider
because it provides the OutputContract dependency.

```php
// In bootstrap/console.php
$app->withServiceProviders([
HttpServiceProvider::class,
ConfigServiceProvider::class,
ConsoleServiceProvider::class, // ← Must come first
DatabaseServiceProvider::class,
ErrorHandlerServiceProvider::class, // ← Depends on ConsoleServiceProvider
]);
```

## Interactive Debug Commands

When an exception occurs, you'll see a prompt where you can type commands:

<table class="table table-dark table-striped">
<thead>
<tr>
<th>Command</th>
<th>Description
</thead>
<tbody>
<tr>
<td>`help`</td>
<td>Show all available commands
<tr>
<td>`trace`</td>
<td>Display full stack trace
<tr>
<td>`frame N`</td>
<td>Inspect frame number N in detail
<tr>
<td>`vars`</td>
<td>Show variables in current frame
<tr>
<td>`source`</td>
<td>Display source code around error
<tr>
<td>`env`</td>
<td>Show environment information (PHP version, memory, etc.)
<tr>
<td>`exit`</td>
<td>Exit debug session

## Example Debug Session

Here's what you see when an exception occurs in a console command:

```bash
$ php bin/larafony process:data

Processing data...

[ERROR] RuntimeException
Data processing failed: Invalid format

in /var/www/app/Commands/ProcessDataCommand.php:24

Debug mode: (type 'help' for available commands) > help

Available commands:
 trace - Show full stack trace
 frame N - Show details for frame N
 vars - Show variables in current frame
 source - Show source code around error
 env - Show environment information
 exit - Exit debug mode

Debug mode: (type 'help' for available commands) > trace

Stack trace:
 #0 ProcessDataCommand->processComplexData()
at /var/www/app/Commands/ProcessDataCommand.php:24

 #1 ProcessDataCommand->handle()
at /var/www/app/Commands/ProcessDataCommand.php:15

 #2 Kernel->handleCommand()
at /var/www/framework/src/Console/Kernel.php:42

Debug mode: (type 'help' for available commands) > frame 0

Frame #0: ProcessDataCommand->processComplexData()
File: /var/www/app/Commands/ProcessDataCommand.php:24

Source code:
 20 │
 21 │ private function processComplexData(): array
 22 │ {
 23 │ // This will trigger the console error handler
> 24 │ throw new \RuntimeException('Data processing failed: Invalid format');
 25 │ }
 26 │ }

Variables:
 $this = ProcessDataCommand {#42}
 $output = Output {#12}

Debug mode: (type 'help' for available commands) > env

Environment Information:
 PHP Version: 8.5.0
 Memory Usage: 4.2 MB / 512 MB
 Execution Time: 0.15s
 Operating System: Linux
 SAPI: cli

Debug mode: (type 'help' for available commands) > exit
```

## Architecture

### Core Components

<table class="table table-dark table-striped">
<thead>
<tr>
<th>Component</th>
<th>Purpose
</thead>
<tbody>
<tr>
<td>`BaseHandler`</td>
<td>Abstract base for all error handlers (web + console)
<tr>
<td>`ConsoleHandler`</td>
<td>CLI-specific error handler
<tr>
<td>`ConsoleRenderer`</td>
<td>Orchestrates rendering of exception details
<tr>
<td>`DebugSession`</td>
<td>Interactive command loop (REPL)
<tr>
<td>`ConsoleRendererFactory`</td>
<td>Constructs renderer with all dependencies

### BaseHandler Abstract Class

Provides common error handling functionality:

```php
abstract class BaseHandler
{
// Register PHP error/exception handlers
public function register(): void
{
set_error_handler([$this, 'handleError']);
set_exception_handler([$this, 'handleException']);
register_shutdown_function([$this, 'handleFatalError']);
}

// Convert PHP errors to exceptions
public function handleError(int $level, string $message, ...): bool
{
throw new ErrorException($message, 0, $level, $file, $line);
}

// Catch fatal errors during shutdown
public function handleFatalError(): void
{
$error = error_get_last();
if ($error !== null && $this->isFatalError($error['type'])) {
$this->handleException(new FatalError(...));
}
}

abstract public function handleException(Throwable $exception): void;
}
```

### Fatal Error Types

The handler catches these fatal errors:

- `E_ERROR` - Fatal run-time errors

- `E_PARSE` - Compile-time parse errors

- `E_CORE_ERROR` - Fatal errors during PHP's initial startup

- `E_COMPILE_ERROR` - Fatal compile-time errors

- `E_USER_ERROR` - User-generated error message

- `E_USER_DEPRECATED` - User-generated deprecation notice

## Comparison with Other Solutions

<table class="table table-dark table-striped">
<thead>
<tr>
<th>Feature</th>
<th>Larafony</th>
<th>Laravel (Tinker + PsySH)</th>
<th>Symfony 7.4
</thead>
<tbody>
<tr>
<td>**Interactive Debugging**</td>
<td>Built-in</td>
<td>External (PsySH)</td>
<td>Not Available
<tr>
<td>**Dependencies**</td>
<td>Zero</td>
<td>Requires psy/psysh</td>
<td>symfony/error-handler
<tr>
<td>**Installation**</td>
<td>Automatic</td>
<td>composer require laravel/tinker</td>
<td>Automatic
<tr>
<td>**Frame Navigation**</td>

</tr>
<tr>
<td>**Variable Inspection**</td>

</tr>
<tr>
<td>**Source Code View**</td>

</tr>
<tr>
<td>**Clean Terminal Output**</td>

<td> (Since 7.4)
<tr>
<td>**Trigger**</td>
<td>On Exception</td>
<td>Manual (tinker command)</td>
<td>On Exception

**💡 Key Difference:**

Larafony's interactive debugger activates automatically when exceptions occur, without requiring
external tools or manual invocation. Laravel's Tinker is primarily a REPL for exploring your application,
not an automatic exception handler. Symfony 7.4 improved console error display but doesn't offer interactive debugging.

## Creating a Test Command

Try it yourself by creating a simple command that throws an exception:

```php
<?php

namespace App\Commands;

use Larafony\Framework\Console\Attributes\Command;
use Larafony\Framework\Console\Contracts\CommandContract;
use Larafony\Framework\Console\Contracts\OutputContract;

#[Command(name: 'test:error', description: 'Test console error handler')]
class TestErrorCommand implements CommandContract
{
public function __construct(private OutputContract $output)
{
}

public function handle(array $options = []): int
{
$this->output->info('About to trigger an exception...');

// This will activate the interactive debugger
throw new \RuntimeException('Test exception for debugging!');
}
}
```

Run it with:

```bash
php bin/larafony test:error
```

You'll immediately drop into the interactive debug session!

## Best Practices

- **Development Only** - The interactive debugger is perfect for development. In production, exceptions should be logged

- **Explore Thoroughly** - Use `trace` to see the full picture, then `frame N` to dive deep

- **Check Variables** - Always inspect variables with `vars` to understand state

- **Exit Cleanly** - Type `exit` to quit, or Ctrl+C for immediate termination

- **Use for Learning** - Great way to understand framework internals and execution flow

## Future Enhancements

Planned improvements for the console debugger:

- Code execution in frame context (eval)

- Search within stack traces

- Export debug session to file

- Custom command extensions

- Integration with logging

**🎉 Conclusion**

Larafony's console debugger represents a significant innovation in PHP framework error handling.
By building this functionality from scratch and integrating it deeply into the framework, we've created
a debugging experience that's both powerful and developer-friendly. No external dependencies, no complex
setup—just pure, interactive debugging magic! 😎
