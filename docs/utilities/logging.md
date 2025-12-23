---
title: "Logging (PSR-3)"
description: "Track application events and errors with Larafony\'s PSR-3 compliant logging system."
---

# Logging (PSR-3)

> **Info:** **PSR-3 Compliant:** Fully implements `Psr\Log\LoggerInterface` with all eight log levels.

## Overview

The logging system provides:

- **PSR-3 Compliant** - Standard logging interface

- **Multiple Handlers** - File, database, custom handlers

- **Multiple Formats** - Text, JSON, XML

- **Log Rotation** - Automatic daily rotation with cleanup

- **Context Support** - Add contextual data to logs

## Basic Usage

### Simple Logging

```php
use Larafony\Framework\Log\Log;

// Info level
Log::info('User logged in', ['user_id' => 123]);

// Error level
Log::error('Database connection failed', [
'database' => 'production',
'error' => $exception->getMessage()
]);

// Debug level
Log::debug('Cache miss', ['key' => 'user:123:profile']);
```

### All Log Levels

```php
// Emergency: System is unusable
Log::emergency('System is down');

// Alert: Action must be taken immediately
Log::alert('Disk space critical');

// Critical: Critical conditions
Log::critical('Application crashed');

// Error: Runtime errors
Log::error('Failed to process payment');

// Warning: Exceptional occurrences that are not errors
Log::warning('High memory usage detected');

// Notice: Normal but significant events
Log::notice('Configuration updated');

// Info: Interesting events
Log::info('User registered');

// Debug: Detailed debug information
Log::debug('Query executed', ['sql' => $sql]);
```

## Log Context

### Adding Context Data

```php
// Context provides additional information
Log::info('Order placed', [
'order_id' => 12345,
'user_id' => 67,
'total' => 99.99,
'items' => ['product_1', 'product_2']
]);

// Context with exception
try {
// Something that might fail
} catch (Exception $e) {
Log::error('Operation failed', [
'exception' => $e->getMessage(),
'trace' => $e->getTraceAsString(),
'file' => $e->getFile(),
'line' => $e->getLine()
]);
}
```

### Placeholder Interpolation

```php
// PSR-3 placeholder syntax
Log::info('User {username} performed {action}', [
'username' => 'john.doe',
'action' => 'logout'
]);
// Output: "User john.doe performed logout"

// Works with all types
Log::warning('Failed login from {ip} with data {data}', [
'ip' => '10.0.0.1',
'data' => ['username' => 'admin', 'attempts' => 5]
]);
```

## Configuration

### File Handler Configuration

```php
// config/logging.php
return [
'channels' => [
[
'handler' => 'file',
'path' => storage_path('logs/app.log'),
'formatter' => 'text',
'max_days' => 14
],
[
'handler' => 'file',
'path' => storage_path('logs/errors.log'),
'formatter' => 'json',
'max_days' => 30
]
]
];
```

### Creating Logger Programmatically

```php
use Larafony\Framework\Log\Logger;
use Larafony\Framework\Log\Handlers\FileHandler;
use Larafony\Framework\Log\Formatters\JsonFormatter;
use Larafony\Framework\Log\Formatters\TextFormatter;
use Larafony\Framework\Log\Rotators\DailyRotator;

$logger = new Logger([
// JSON logs with 30-day rotation
new FileHandler(
logPath: '/var/log/app/application.log',
formatter: new JsonFormatter(),
rotator: new DailyRotator(maxDays: 30)
),

// Text logs with 7-day rotation
new FileHandler(
logPath: '/var/log/app/debug.log',
formatter: new TextFormatter(),
rotator: new DailyRotator(maxDays: 7)
),
]);
```

## Handlers

### File Handler

```php
use Larafony\Framework\Log\Handlers\FileHandler;
use Larafony\Framework\Log\Formatters\TextFormatter;

$handler = new FileHandler(
logPath: '/var/log/app/app.log',
formatter: new TextFormatter()
);

$logger = new Logger([$handler]);
```

### Database Handler

```php
use Larafony\Framework\Log\Handlers\DatabaseHandler;

// Stores logs in database
$handler = new DatabaseHandler();

$logger = new Logger([$handler]);

// Logs are automatically saved to the database
$logger->error('Critical error', ['details' => 'Some error']);
```

## Formatters

### Text Formatter

```php
use Larafony\Framework\Log\Formatters\TextFormatter;

$formatter = new TextFormatter();

// Output format:
// [2025-01-15 14:30:45] ERROR: Database connection failed
// Context: {"database":"production","error":"Connection timeout"}
// Metadata: {"timestamp":"2025-01-15T14:30:45+00:00"}
```

### JSON Formatter

```php
use Larafony\Framework\Log\Formatters\JsonFormatter;

$formatter = new JsonFormatter();

// Output format (pretty-printed JSON):
// {
// "level": "error",
// "message": "Database connection failed",
// "context": {
// "database": "production",
// "error": "Connection timeout"
// },
// "metadata": {
// "timestamp": "2025-01-15T14:30:45+00:00"
// }
// }
```

### XML Formatter

```php
use Larafony\Framework\Log\Formatters\XmlFormatter;

$formatter = new XmlFormatter();

// Output format:
// <?xml version="1.0"?>
// <log>
// <level>error</level>
// <message>Database connection failed</message>
// <context>
// <database>production</database>
// <error>Connection timeout</error>
// </context>
// </log>
```

## Log Rotation

### Daily Rotation

```php
use Larafony\Framework\Log\Rotators\DailyRotator;

// Rotate daily, keep logs for 14 days
$rotator = new DailyRotator(maxDays: 14);

// Logs are automatically rotated:
// app.log (current)
// app-2025-01-14.log
// app-2025-01-13.log
// ...
// (older logs automatically deleted)
```

## Practical Examples

### Example 1: Application Logging

```php
class UserService
{
public function createUser(array $data): User
{
Log::info('Creating new user', [
'email' => $data['email']
]);

try {
$user = User::create($data);

Log::info('User created successfully', [
'user_id' => $user->id,
'email' => $user->email
]);

return $user;
} catch (Exception $e) {
Log::error('Failed to create user', [
'email' => $data['email'],
'error' => $e->getMessage(),
'trace' => $e->getTraceAsString()
]);

throw $e;
}
}
}
```

### Example 2: API Request Logging

```php
class ApiMiddleware
{
public function handle(ServerRequestInterface $request): ResponseInterface
{
$startTime = microtime(true);

Log::info('API request started', [
'method' => $request->getMethod(),
'uri' => (string) $request->getUri(),
'user_agent' => $request->getHeaderLine('User-Agent')
]);

try {
$response = $this->next($request);

$duration = microtime(true) - $startTime;

Log::info('API request completed', [
'method' => $request->getMethod(),
'uri' => (string) $request->getUri(),
'status' => $response->getStatusCode(),
'duration' => round($duration * 1000, 2) . 'ms'
]);

return $response;
} catch (Exception $e) {
Log::error('API request failed', [
'method' => $request->getMethod(),
'uri' => (string) $request->getUri(),
'error' => $e->getMessage()
]);

throw $e;
}
}
}
```

### Example 3: Performance Monitoring

```php
class PerformanceLogger
{
public static function logSlowQuery(string $sql, float $duration): void
{
if ($duration > 1.0) {
Log::warning('Slow query detected', [
'sql' => $sql,
'duration' => $duration,
'threshold' => 1.0
]);
}
}

public static function logHighMemoryUsage(): void
{
$memory = memory_get_usage(true) / 1024 / 1024;

if ($memory > 100) {
Log::warning('High memory usage', [
'memory_mb' => round($memory, 2),
'threshold' => 100
]);
}
}
}
```

## Custom Handler

### Creating Custom Handler

```php
use Larafony\Framework\Log\Contracts\HandlerContract;
use Larafony\Framework\Log\Message;

class SlackHandler implements HandlerContract
{
public function __construct(
private readonly string $webhookUrl,
private readonly string $channel
) {}

public function handle(Message $message): void
{
// Only send critical logs to Slack
if ($message->level->value !== 'critical') {
return;
}

$payload = json_encode([
'channel' => $this->channel,
'text' => $message->message,
'attachments' => [[
'color' => 'danger',
'fields' => [
['title' => 'Level', 'value' => $message->level->value],
['title' => 'Time', 'value' => $message->metadata->timestamp]
]
]]
]);

// Send to Slack webhook
$ch = curl_init($this->webhookUrl);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_exec($ch);
curl_close($ch);
}
}

// Usage
$logger = new Logger([
new FileHandler('/var/log/app.log', new TextFormatter()),
new SlackHandler('https://hooks.slack.com/...', '#alerts')
]);
```

## Best Practices

#### Do

- Use appropriate log levels (info, error, debug, etc.)

- Add context to provide useful information

- Use structured logging (arrays) instead of string concatenation

- Implement log rotation to manage disk space

- Log exceptions with stack traces

- Use JSON formatter for log aggregation tools

#### Don't

- Don't log sensitive data (passwords, API keys, credit cards)

- Don't log in tight loops (performance impact)

- Don't use wrong log levels (debug for errors, etc.)

- Don't forget to implement log rotation in production

## Security Considerations

> **Danger:** **Security Warning:** Never log sensitive data like passwords, API keys, or credit card numbers.

```php
// WRONG - Logs sensitive data
Log::info('User login', [
'email' => $email,
'password' => $password // NEVER DO THIS!
]);

// CORRECT - Exclude sensitive data
Log::info('User login attempt', [
'email' => $email,
'ip' => $request->getAttribute('ip_address')
]);
```

## Next Steps

#### Configuration

Configure logging handlers and formatters.

[
Read Guide 
](/docs/config)

#### Container

Use dependency injection for logger instances.

[
Read Guide 
](/docs/container)
