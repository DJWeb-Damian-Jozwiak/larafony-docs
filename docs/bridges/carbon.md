---
title: "Carbon Clock Bridge"
description: "PSR-20 Clock implementation using Carbon for powerful date/time handling."
---

# Carbon Clock Bridge

## Installation

```bash
composer require larafony/clock-carbon
```

## Configuration

```php
use Larafony\Clock\Carbon\ServiceProviders\CarbonServiceProvider;

$app->withServiceProviders([
CarbonServiceProvider::class
]);
```

## Basic Usage

```php
use Psr\Clock\ClockInterface;
use Larafony\Clock\Carbon\CarbonClock;

final class ReportController extends Controller
{
#[Route('/reports/daily', methods: ['GET'])]
public function daily(ClockInterface $clock): ResponseInterface
{
$now = $clock->now(); // Returns CarbonImmutable

$report = Report::query()
->whereDate('created_at', $now->toDateString())
->get();

return new ResponseFactory()->createJsonResponse($report);
}
}
```

## Date Manipulation

```php
use Larafony\Clock\Carbon\CarbonClock;

$clock = CarbonClock::fromTimezone('Europe/Warsaw');
$now = $clock->now();

// Add/subtract time
$tomorrow = $now->addDay();
$lastWeek = $now->subWeek();
$nextMonth = $now->addMonth();

// Human readable
$diff = $now->diffForHumans(); // "just now"

// Formatting
$formatted = $now->format('Y-m-d H:i:s');
$iso = $now->toIso8601String();
```

## Timezone Support

```php
// Create clock for specific timezone
$warsawClock = CarbonClock::fromTimezone('Europe/Warsaw');
$nyClock = CarbonClock::fromTimezone('America/New_York');

// Compare times
$warsawNow = $warsawClock->now();
$nyNow = $nyClock->now();

echo $warsawNow->diffInHours($nyNow); // 6
```

## Features

- **PSR-20 compatible** - Implements `ClockInterface`

- **Immutable by default** - Uses `CarbonImmutable`

- **Timezone aware** - Full timezone support

- **Human readable** - `diffForHumans()` and more

- **Rich API** - All Carbon methods available
