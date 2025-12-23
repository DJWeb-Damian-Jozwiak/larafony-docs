---
title: "DebugBar & Model Eager Loading"
description: "Professional DebugBar for real-time application insights and N+1 query prevention through model eager loading"
---

# DebugBar & Model Eager Loading

> **Info:** **Chapter 27:** Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch. Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

Chapter 27 introduces two critical development features: a **professional DebugBar** for real-time application insights and **N+1 query prevention** through model eager loading. The DebugBar provides comprehensive debugging information during development, while eager loading ensures production-grade performance by eliminating the notorious N+1 query problem.

The DebugBar is a non-intrusive toolbar injected into HTML responses, collecting data through event listeners without modifying application logic. It tracks database queries with execution time and backtrace, cache operations (hits/misses/writes/deletes) with hit ratio calculation, view rendering with template names and data, route matching with parameters and controller info, request/response details (method, URI, headers, status), application performance (execution time, memory usage, peak memory), and timeline visualization showing the complete request lifecycle.

Model eager loading solves the N+1 query problem by loading related models in bulk rather than one-by-one. Instead of executing 1 + N queries (one to fetch parent models, then one query per parent for its relations), eager loading executes just 2 queries (one for parents, one for all related models), dramatically reducing database load and improving response times.

> **Warning:** **Key Performance Impact:** **Without Eager Loading:** 101 queries for 100 users with roles (1 + 100) **With Eager Loading:** 2 queries for 100 users with roles (1 + 1) **Reduction:** 98% fewer queries

## Key Components

### DebugBar System

- **DebugBar** - Central orchestrator managing data collectors with `addCollector()` for registration, `collect()` for gathering data from all collectors, `enable()/disable()` for toggling, and `isEnabled()` for status checking

- **DataCollectorContract** - Interface for collectors with `collect(): array` to gather data and `getName(): string` for identification

- **InjectDebugBar Middleware** - PSR-15 middleware injecting toolbar into HTML responses by checking Content-Type (must be text/html), verifying status code (only 2xx/3xx, not errors), rendering toolbar view, and inserting before `&lt;/body&gt;` tag

### Data Collectors

Each collector implements DataCollectorContract and listens to framework events:

- **QueryCollector** - Listens to QueryExecuted events, tracks all SQL queries with execution time, bindings, connection name, and backtrace (filtered to exclude framework internals), calculates total query time and count

- **CacheCollector** - Listens to CacheHit, CacheMissed, KeyWritten, KeyForgotten events, tracks operations with timestamps, calculates hit ratio percentage, monitors total cache size (bytes written)

- **ViewCollector** - Listens to ViewRendered events, tracks rendered views with names and data, measures rendering time per view

- **RouteCollector** - Listens to RouteMatched events, captures route name, URI pattern, HTTP method, controller/action, and matched parameters

- **RequestCollector** - Captures request details including HTTP method, URI, headers, query parameters, and request body

- **PerformanceCollector** - Measures execution time from REQUEST_TIME_FLOAT, tracks memory usage (current, peak, delta), formats bytes to human-readable units (B, KB, MB, GB)

- **TimelineCollector** - Creates visual timeline by listening to ApplicationBooting, ApplicationBooted, RouteMatched, QueryExecuted, ViewRendering, ViewRendered events, tracks event start/end times, calculates durations in milliseconds, and sorts chronologically

### Model Eager Loading

- **ModelQueryBuilder** - Enhanced with `with(array $relations)` method for specifying relations to eager load, supports nested relations via dot notation (e.g., 'user.profile.avatar'), stores eager load configuration in `$eagerLoad` array

- **EagerRelationsLoader** - Orchestrates eager loading by iterating through configured relations, delegating to appropriate relation loader, and passing nested relation configuration

- **RelationLoaderContract** - Interface for relation loaders with `load(array $models, string $relationName, RelationContract $relation, array $nested): void`

- **BelongsToLoader** - Loads belongsTo relations by collecting foreign key values from parent models, executing single whereIn query to fetch all related models, indexing by primary key for O(1) lookup, and assigning to parent models

- **HasManyLoader** - Loads hasMany relations by collecting local key values, executing single whereIn query, grouping results by foreign key, and assigning arrays to parent models

- **BelongsToManyLoader** - Loads belongsToMany relations via pivot tables by collecting parent IDs, querying pivot table, fetching related models, and grouping by parent ID

- **HasManyThroughLoader** - Loads hasManyThrough relations by traversing intermediate models, executing optimized join query, and grouping results

## Usage Examples

### DebugBar Integration

The DebugBar is automatically enabled in development environments and displays at the bottom of HTML pages:

```php
// config/app.php - DebugBar is registered via DebugBarServiceProvider
use Larafony\Framework\DebugBar\ServiceProviders\DebugBarServiceProvider;

return [
'providers' => [
// ... other providers
DebugBarServiceProvider::class,
],
];

// config/debugbar.php - Configure DebugBar behavior
use Larafony\Framework\Config\Environment\EnvReader;
use Larafony\Framework\DebugBar\Collectors\CacheCollector;
use Larafony\Framework\DebugBar\Collectors\PerformanceCollector;
use Larafony\Framework\DebugBar\Collectors\QueryCollector;
use Larafony\Framework\DebugBar\Collectors\RequestCollector;
use Larafony\Framework\DebugBar\Collectors\RouteCollector;
use Larafony\Framework\DebugBar\Collectors\TimelineCollector;
use Larafony\Framework\DebugBar\Collectors\ViewCollector;

return [
'enabled' => EnvReader::read('APP_DEBUG', false),

'collectors' => [
'queries' => QueryCollector::class,
'cache' => CacheCollector::class,
'views' => ViewCollector::class,
'route' => RouteCollector::class,
'request' => RequestCollector::class,
'performance' => PerformanceCollector::class,
'timeline' => TimelineCollector::class,
]
];

// The middleware is automatically registered in HTTP kernel
// No manual configuration needed!
```

**What You See:**

When you load any HTML page in development, the DebugBar appears at the bottom showing:

- **Queries Tab:** All executed queries with syntax-highlighted SQL, execution time, backtrace to source, and bindings

- **Cache Tab:** Cache operations with hit/miss ratio, total operations, and size metrics

- **Views Tab:** Rendered templates with data passed to each view

- **Route Tab:** Matched route details with parameters

- **Request Tab:** Full request information (method, URI, headers, body)

- **Performance Tab:** Execution time, memory usage, peak memory

- **Timeline Tab:** Visual waterfall chart of application lifecycle

### Basic Eager Loading

```php
use App\Models\User;

// ❌ N+1 Problem (101 queries for 100 users)
$users = User::query()->get(); // 1 query

foreach ($users as $user) {
echo $user->role->name; // 100 queries (one per user)
}
// Total: 101 queries

// ✅ With Eager Loading (2 queries for 100 users)
$users = User::query()->with(['role'])->get(); // 2 queries (users + roles)

foreach ($users as $user) {
echo $user->role->name; // No query - already loaded
}
// Total: 2 queries
```

**DebugBar Shows:**

- Without eager loading: 101 queries, ~150ms total time

- With eager loading: 2 queries, ~3ms total time

- **Performance improvement:** 50x faster

### Nested Eager Loading

```php
use App\Models\Post;

// Load posts with author and author's profile
$posts = Post::query()
 ->with(['author.profile'])
 ->get();

// 3 queries total:
// 1. SELECT * FROM posts
// 2. SELECT * FROM users WHERE id IN (...)
// 3. SELECT * FROM profiles WHERE user_id IN (...)

foreach ($posts as $post) {
echo $post->author->profile->bio; // No queries - all loaded
}
```

**Nested Relation Syntax:**

- `'author'` - Load author relation

- `'author.profile'` - Load author AND author's profile

- `'author.profile.avatar'` - Load author, profile, and avatar (3 levels deep)

### Multiple Relations

```php
use App\Models\User;

// Load multiple relations at once
$users = User::query()
 ->with(['role', 'permissions', 'posts'])
 ->get();

// 4 queries total:
// 1. SELECT * FROM users
// 2. SELECT * FROM roles WHERE id IN (...)
// 3. SELECT * FROM permissions WHERE user_id IN (...)
// 4. SELECT * FROM posts WHERE author_id IN (...)

foreach ($users as $user) {
echo $user->role->name;
echo count($user->permissions);
echo count($user->posts);
// All data already loaded - no additional queries
}
```

### Complex Nested Loading

```php
use App\Models\Category;

// Deep nesting with multiple branches
$categories = Category::query()
 ->with([
'posts.author.profile', // Posts -> Authors -> Profiles
'posts.comments.user', // Posts -> Comments -> Users
'posts.tags', // Posts -> Tags
])
 ->get();

// 7 queries total:
// 1. SELECT * FROM categories
// 2. SELECT * FROM posts WHERE category_id IN (...)
// 3. SELECT * FROM users WHERE id IN (...) -- authors
// 4. SELECT * FROM profiles WHERE user_id IN (...)
// 5. SELECT * FROM comments WHERE post_id IN (...)
// 6. SELECT * FROM users WHERE id IN (...) -- comment authors
// 7. SELECT * FROM tags JOIN post_tag WHERE post_id IN (...)

foreach ($categories as $category) {
foreach ($category->posts as $post) {
echo $post->author->profile->bio;
foreach ($post->comments as $comment) {
echo $comment->user->name;
}
foreach ($post->tags as $tag) {
echo $tag->name;
}
}
}
// All data accessed without additional queries!
```

**DebugBar Timeline Shows:**

- Query 1: Categories (2ms)

- Query 2: Posts (3ms)

- Query 3: Authors (2ms)

- Query 4: Profiles (1ms)

- Query 5: Comments (4ms)

- Query 6: Comment Users (2ms)

- Query 7: Tags (3ms)

- **Total: 17ms for complete dataset**

## Implementation Details

### DebugBar

**Location:** `src/Larafony/DebugBar/DebugBar.php`

**Purpose:** Central orchestrator managing all data collectors and coordinating data collection.

**Key Methods:**

- `addCollector(string $name, DataCollectorContract $collector): void` - Register a collector

- `collect(): array&lt;string, mixed&gt;` - Gather data from all collectors

- `enable(): void` - Enable DebugBar

- `disable(): void` - Disable DebugBar

- `isEnabled(): bool` - Check if DebugBar is enabled

### InjectDebugBar Middleware

**Location:** `src/Larafony/DebugBar/Middleware/InjectDebugBar.php`

**Purpose:** PSR-15 middleware that injects DebugBar toolbar HTML into responses.

**Injection Logic:**

- Check if DebugBar is enabled - if not, return original response

- Check Content-Type - must contain 'text/html'

- Check status code - must be &lt; 400 (not error page)

- Find `&lt;/body&gt;` tag in response body

- Render toolbar view with collected data

- Insert toolbar HTML before `&lt;/body&gt;`

- Return modified response

**Safety Checks:**

- Only injects into HTML responses (not JSON, XML, etc.)

- Only injects into successful responses (not 404, 500, etc.)

- Only injects if `&lt;/body&gt;` tag exists

- Gracefully handles missing conditions

### DebugBarServiceProvider

**Location:** `src/Larafony/DebugBar/ServiceProviders/DebugBarServiceProvider.php`

**Purpose:** Service provider responsible for bootstrapping DebugBar with configuration-driven collector registration.

**Bootstrap Algorithm:**

- Check if DebugBar is enabled in config - **early return if disabled** (zero overhead in production)

- Create DebugBar instance

- Load collectors configuration from `config/debugbar.php`

- Iterate through collector class names

- Resolve each collector from container (supports DI)

- Register collector with DebugBar

- Store collector instances for event listener discovery

- Enable DebugBar

- Register DebugBar singleton in container

- Discover and register event listeners using ListenerDiscovery

> **Success:** **Performance Optimization:** The provider uses an early return pattern when DebugBar is disabled, ensuring **zero overhead** in production environments - no collectors are instantiated, no event listeners registered, and no memory allocated for debugging infrastructure.

### EagerRelationsLoader

**Location:** `src/Larafony/Database/ORM/EagerLoading/EagerRelationsLoader.php`

**Purpose:** Orchestrate eager loading of model relations to prevent N+1 queries.

**Algorithm:**

- For each configured relation:

- Get relation instance from first model

- Determine loader type (BelongsTo, HasMany, etc.)

- Delegate to specific loader

- Pass nested relations for recursive loading

### HasManyLoader

**Location:** `src/Larafony/Database/ORM/EagerLoading/HasManyLoader.php`

**Purpose:** Load hasMany relations efficiently with single query.

**Algorithm:**

- Extract foreign_key, local_key, related class from relation via reflection

- Collect local key values from all parent models

- Execute single `whereIn(foreign_key, local_keys)` query

- Support nested eager loading recursively

- Group results by foreign key value

- Assign grouped arrays to parent models

```php
// Given: 100 users, each with multiple posts
// Without eager loading: 1 + 100 queries
// With eager loading: 1 + 1 queries

$users = User::query()->with(['posts'])->get();

// 2 queries:
// SELECT * FROM users
// SELECT * FROM posts WHERE user_id IN (1,2,3,...,100)
```

## Testing

The DebugBar and eager loading features are tested through integration tests:

### DebugBar Integration Tests

**Coverage:** Tests verify:

- Middleware injection into HTML responses

- Collector data gathering

- Event listener registration

- Response modification without corruption

- Conditional injection (only HTML, only 2xx/3xx)

### Eager Loading Tests

**Coverage:** Tests verify:

- N+1 query prevention

- Single query execution per relation

- Nested relation loading

- Multiple relation loading

- Relation data integrity

- Support for all relation types (BelongsTo, HasMany, BelongsToMany, HasManyThrough)

```php
// Without eager loading
$users = User::query()->get();
$this->assertQueryCount(101); // 1 + 100

// With eager loading
$users = User::query()->with(['role'])->get();
$this->assertQueryCount(2); // 1 + 1
```

> **Success:** **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
