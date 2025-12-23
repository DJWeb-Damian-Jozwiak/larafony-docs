---
title: "Bridge Packages"
description: "Official bridge packages to integrate popular PHP libraries with Larafony Framework."
---

# Bridge Packages

> **Info:** **Drop-in Replacements:** Each bridge replaces Larafony's native implementation by swapping the service provider. No code changes required - same interfaces, different engine.

## Available Bridges

<h5 class="card-title">Guzzle HTTP</h5>

Replace native cURL client with Guzzle for async requests, middleware, and advanced HTTP features.

[Learn more →](/bridges/guzzle)

<h5 class="card-title">Monolog</h5>

Replace native Logger with Monolog for 50+ handlers, processors, and formatters.

[Learn more →](/bridges/monolog)

<h5 class="card-title">Symfony Mailer</h5>

Replace native SMTP with Symfony Mailer for SES, Mailgun, SendGrid, and more.

[Learn more →](/bridges/symfony-mailer)

<h5 class="card-title">Flysystem</h5>

Unified filesystem abstraction for local, S3, FTP, SFTP, and more backends.

[Learn more →](/bridges/flysystem)

<h5 class="card-title">Carbon Clock</h5>

PSR-20 Clock implementation with Carbon for powerful date/time manipulation.

[Learn more →](/bridges/carbon)

<h5 class="card-title">PHP dotenv</h5>

Enhanced .env parsing with variable expansion, multiline values, and validation.

[Learn more →](/bridges/phpdotenv)

<h5 class="card-title">Twig Templates</h5>

Use Twig templating engine as an alternative to Blade.

[Learn more →](/bridges/twig)

<h5 class="card-title">Smarty Templates</h5>

Use Smarty templating engine as an alternative to Blade.

[Learn more →](/bridges/smarty)

<h5 class="card-title">PHP DebugBar</h5>

Replace native DebugBar with maximebf/debugbar for visual in-browser debugging.

[Learn more →](/bridges/debugbar)

## How Bridges Work

Larafony bridges follow a simple pattern: swap the service provider in your `bootstrap.php`
and the bridge takes over. Your application code remains unchanged because bridges implement the same interfaces.

```php
// bootstrap.php
$app->withServiceProviders([
// Comment out native provider
// Larafony\Framework\Log\ServiceProviders\LogServiceProvider::class,

// Add bridge provider instead
Larafony\Log\Monolog\ServiceProviders\MonologServiceProvider::class,
]);
```

> **Success:** **PSR Standards:** All bridges implement PSR interfaces (PSR-3, PSR-18, PSR-20), ensuring compatibility with any PSR-compliant library.
