---
title: "Monolog Logging Bridge"
description: "Professional logging with Monolog - multiple channels, 50+ handlers, and PSR-3 compatibility."
---

# Monolog Logging Bridge

## Installation

```bash
composer require larafony/log-monolog
```

## Configuration

Register the service provider in your `bootstrap.php`:

```php
use Larafony\Log\Monolog\ServiceProviders\MonologServiceProvider;

$app->withServiceProviders([
MonologServiceProvider::class
]);
```

Create `config/logging.php`:

```php
return [
'default' => 'stack',

'channels' => [
'stack' => [
'driver' => 'stack',
'channels' => ['daily', 'slack'],
],

'single' => [
'driver' => 'single',
'path' => storage_path('logs/app.log'),
'level' => 'debug',
],

'daily' => [
'driver' => 'daily',
'path' => storage_path('logs/app.log'),
'level' => 'debug',
'days' => 14,
],

'slack' => [
'driver' => 'slack',
'url' => env('LOG_SLACK_WEBHOOK_URL'),
'level' => 'critical',
],

'syslog' => [
'driver' => 'syslog',
'level' => 'debug',
],
],
];
```

## Basic Usage

```php
use Psr\Log\LoggerInterface;
use Larafony\Framework\Web\Controller;
use Larafony\Framework\Routing\Advanced\Attributes\Route;
use Larafony\Framework\Http\Factories\ResponseFactory;

final class OrderController extends Controller
{
#[Route('/orders/<id:\d+>', methods: ['POST'])]
public function process(LoggerInterface $logger, int $id): \Psr\Http\Message\ResponseInterface
{
$logger->info('Processing order', ['order_id' => $id]);

try {
// Process order...
$logger->debug('Order validated successfully');

return new ResponseFactory()->createJsonResponse(['status' => 'completed']);
} catch (\Exception $e) {
$logger->error('Order processing failed', [
'order_id' => $id,
'error' => $e->getMessage(),
'trace' => $e->getTraceAsString(),
]);

return new ResponseFactory()->createJsonResponse(['error' => 'Failed'], 500);
}
}
}
```

## Multiple Channels

```php
use Larafony\Log\Monolog\MonologManager;

final class AlertController extends Controller
{
#[Route('/send-alert', methods: ['POST'])]
public function sendAlert(MonologManager $manager): ResponseInterface
{
// Log to specific channel
$manager->channel('slack')->critical('Server down!');
$manager->channel('daily')->info('Daily report generated');

// Log to multiple channels at once
$manager->stack(['daily', 'slack'])->alert('Security breach detected');

return new ResponseFactory()->createJsonResponse(['alerted' => true]);
}
}
```

## Contextual Logging

```php
// Add context to all subsequent logs
$logger->withContext(['request_id' => $requestId, 'user_id' => $userId]);

$logger->info('Processing started'); // includes request_id and user_id
$logger->info('Step 1 complete'); // includes request_id and user_id
$logger->info('Processing complete'); // includes request_id and user_id
```

## Available Handlers

- **StreamHandler** - Log to files

- **RotatingFileHandler** - Daily log rotation

- **SlackWebhookHandler** - Send to Slack

- **SyslogHandler** - System log

- **NativeMailerHandler** - Send via email

- **RedisHandler** - Log to Redis

- **ElasticsearchHandler** - Log to Elasticsearch

- And 40+ more via Monolog ecosystem

## Features

- **PSR-3 compatible** - Implements `LoggerInterface`

- **Multiple channels** - Log to different destinations

- **Stack channels** - Combine multiple channels

- **Daily rotation** - Automatic log file rotation

- **Formatters** - JSON, Line, HTML formats

- **Processors** - Add extra context automatically

> **Info:** **Why Monolog?** While Larafony includes a built-in PSR-3 logger, Monolog offers 50+ handlers for different destinations, processors to enrich log data automatically, and industry-standard logging used by Symfony, Laravel, and many others.
