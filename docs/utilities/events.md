---
title: "Event System (PSR-14)"
description: "Powerful event-driven architecture based on PSR-14, enabling loosely coupled application components through publish-subscribe patterns"
---

# Event System (PSR-14)

> **Info:** **Chapter 26:** Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch. Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 26 introduces a powerful event-driven architecture based on **PSR-14 (Event Dispatcher)**, enabling loosely coupled application components through publish-subscribe patterns. This implementation provides attribute-based listener registration, automatic event type inference, priority-based execution, and stoppable event propagation — all while maintaining strict PSR compliance.

The event system serves as the foundation for framework-wide observability, powering features like database query logging, cache monitoring, view rendering hooks, and route matching events. It eliminates tight coupling between components by allowing any part of the application to react to events without direct dependencies.

Key features include automatic listener discovery using PHP 8.5 attributes (`#[Listen]`), priority-based listener execution (higher priority = earlier execution), container-based listener resolution for dependency injection, and framework events for application lifecycle (booting/booted), database operations (queries, transactions), cache operations (hit/miss/write/forget), view rendering (before/after), and route matching.

## Key Components

### Event Dispatcher

- **EventDispatcher** - PSR-14 compliant event dispatcher with support for stoppable events (implements StoppableEventInterface), listener priority ordering (higher executes first), and sequential execution with early termination for stopped events

- **ListenerProvider** - PSR-14 listener provider managing event-to-listener mappings with priority-based sorting (krsort for descending order), container-based listener resolution (automatic DI), and support for both callable and array-based listeners ([ClassName, 'method'])

- **ListenerDiscovery** - Automatic listener registration via reflection scanning public methods for `#[Listen]` attributes, event type inference from method parameter types, and support for both class names and object instances

### Attribute-Based Registration

- **#[Listen]** - Method attribute for marking event listeners with optional event class specification (auto-inferred from parameter type if not provided), configurable priority (default 0, higher = earlier execution), and repeatable attribute (single method can listen to multiple events)

### Framework Events

The framework dispatches events at critical points in the application lifecycle:

#### Application Events

- **ApplicationBooting** - Dispatched before service providers boot

- **ApplicationBooted** - Dispatched after all service providers have booted

#### Database Events

- **QueryExecuted** - Dispatched after each SQL query with query details (SQL, bindings, execution time, connection name) and backtrace for debugging

- **TransactionBeginning** - Dispatched when database transaction starts

- **TransactionCommitted** - Dispatched after successful transaction commit

- **TransactionRolledBack** - Dispatched after transaction rollback

#### Cache Events

- **CacheHit** - Dispatched when cache key is found (includes key and value)

- **CacheMissed** - Dispatched when cache key is not found (includes key only)

- **KeyWritten** - Dispatched after cache write (includes key, value, TTL)

- **KeyForgotten** - Dispatched after cache deletion (includes key)

#### View Events

- **ViewRendering** - Dispatched before view rendering (includes view name and data, allows modification)

- **ViewRendered** - Dispatched after view rendering (includes view name, data, and rendered output)

#### Routing Events

- **RouteMatched** - Dispatched when route is matched to request (includes route name, URI pattern, controller, matched parameters)

### Stoppable Events

- **StoppableEvent** - Abstract base class implementing PSR-14 StoppableEventInterface with `stopPropagation()` method to halt listener execution and `isPropagationStopped()` to check stopped state

## PSR Standards Implemented

- **PSR-14**: Event Dispatcher - Full implementation with EventDispatcher (dispatches events to listeners), ListenerProvider (provides listeners for events), and StoppableEventInterface (allows stopping propagation)

- **PSR-11**: Container Interface - Used for automatic listener instantiation and dependency injection

## New Attributes

### #[Listen]

Marks a method as an event listener with optional event class and priority configuration.

**Parameters:**

- `event` (class-string|null) - Event class name. If null, inferred from first method parameter type

- `priority` (int) - Listener priority. Higher values execute first. Default: 0

**Target:** Methods only (Attribute::TARGET_METHOD)

**Repeatable:** Yes (Attribute::IS_REPEATABLE) - one method can listen to multiple events

```php
use Larafony\Framework\Events\Attributes\Listen;
use Larafony\Framework\Events\Database\QueryExecuted;
use Larafony\Framework\Events\Cache\CacheHit;

class MyListener
{
// Explicit event class
#[Listen(event: QueryExecuted::class, priority: 10)]
public function onQuery(QueryExecuted $event): void
{
// High priority (10) - executes before priority 0
}

// Auto-inferred from parameter type
#[Listen]
public function onCacheHit(CacheHit $event): void
{
// Event type inferred from parameter
}

// Multiple listeners on same method
#[Listen(event: CacheHit::class)]
#[Listen(event: CacheMissed::class)]
public function onCacheAccess(object $event): void
{
// Handles both CacheHit and CacheMissed
}
}
```

## Usage Examples

### Basic Event Listening

```php
use Larafony\Framework\Events\EventDispatcher;
use Larafony\Framework\Events\ListenerProvider;
use Larafony\Framework\Events\Database\QueryExecuted;

// Manual listener registration
$provider = new ListenerProvider();
$dispatcher = new EventDispatcher($provider);

// Register listener with priority
$provider->listen(
QueryExecuted::class,
function (QueryExecuted $event) {
echo "Query: {$event->sql}\n";
echo "Time: {$event->time}ms\n";
},
priority: 5
);

// Dispatch event
$event = new QueryExecuted(
sql: 'SELECT * FROM users WHERE id = ?',
rawSql: 'SELECT * FROM users WHERE id = 1',
bindings: [1],
time: 2.45,
connection: 'mysql'
);

$dispatcher->dispatch($event);
```

### Attribute-Based Listeners

```php
use Larafony\Framework\Events\Attributes\Listen;
use Larafony\Framework\Events\Database\QueryExecuted;
use Larafony\Framework\Events\Cache\CacheHit;
use Larafony\Framework\Events\Cache\CacheMissed;

class ApplicationMonitor
{
// High priority query logging
#[Listen(priority: 100)]
public function logSlowQueries(QueryExecuted $event): void
{
if ($event->time > 100) {
// Log slow queries (>100ms)
error_log("SLOW QUERY: {$event->sql} ({$event->time}ms)");
}
}

// Cache monitoring
#[Listen]
public function trackCacheHitRate(CacheHit $event): void
{
// Increment cache hit counter
$this->incrementMetric('cache.hits');
}

#[Listen]
public function trackCacheMissRate(CacheMissed $event): void
{
// Increment cache miss counter
$this->incrementMetric('cache.misses');
}

// Multiple events, one handler
#[Listen(event: CacheHit::class)]
#[Listen(event: CacheMissed::class)]
public function logCacheAccess(object $event): void
{
$type = $event instanceof CacheHit ? 'HIT' : 'MISS';
echo "Cache {$type}: {$event->key}\n";
}

private function incrementMetric(string $name): void
{
// Implementation...
}
}
```

### Stoppable Events

```php
use Larafony\Framework\Events\StoppableEvent;
use Larafony\Framework\Events\Attributes\Listen;

// Custom stoppable event
class UserRegistering extends StoppableEvent
{
public function __construct(
public string $email,
public string $password,
public ?string $reason = null
) {
}
}

class RegistrationValidator
{
#[Listen(priority: 100)]
public function validateEmail(UserRegistering $event): void
{
if (!filter_var($event->email, FILTER_VALIDATE_EMAIL)) {
$event->reason = 'Invalid email format';
$event->stopPropagation(); // Stop further listeners
}
}

#[Listen(priority: 50)]
public function checkBlacklist(UserRegistering $event): void
{
if ($this->isBlacklisted($event->email)) {
$event->reason = 'Email is blacklisted';
$event->stopPropagation();
}
}

#[Listen(priority: 0)]
public function createUser(UserRegistering $event): void
{
// Only executes if not stopped
echo "Creating user: {$event->email}\n";
}

private function isBlacklisted(string $email): bool
{
return false;
}
}

// Usage
$event = new UserRegistering('spam@example.com', 'password');
$dispatcher->dispatch($event);

if ($event->isPropagationStopped()) {
echo "Registration failed: {$event->reason}\n";
}
```

## Testing

The event system is covered by comprehensive test suites:

### EventDispatcherTest

**Location:** `tests/Larafony/Events/EventDispatcherTest.php`

**Coverage:** 6 tests covering:

- Basic event dispatching

- Multiple listeners on same event

- Priority-based execution order

- Stoppable event propagation

- Event modification by listeners

- Empty listener handling

**All tests pass:** ✅ 6/6 tests

### ListenerProviderTest

**Location:** `tests/Larafony/Events/ListenerProviderTest.php`

**Coverage:** 8 tests covering listener provider functionality

**All tests pass:** ✅ 8/8 tests

### ListenerDiscoveryTest

**Location:** `tests/Larafony/Events/ListenerDiscoveryTest.php`

**Coverage:** 7 tests covering listener discovery functionality

**All tests pass:** ✅ 7/7 tests

**Total Test Coverage:**

- 21 tests across 3 test suites

- 40+ assertions

- 100% pass rate

- Covers all PSR-14 functionality

> **Success:** **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
