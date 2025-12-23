---
title: "Queue & Jobs"
description: "Enterprise-grade job scheduling and queue processing with ORM integration, Clock-based timestamps, UUID support, and comprehensive failed job handling"
---

# Queue & Jobs

> **Info:** **Production Ready:** Built with ORM persistence, UUID primary keys for distributed systems, Clock integration for testable time operations, and complete failed job recovery system.

## Overview

Larafony's scheduler system combines two powerful capabilities:

- **Queue System** - Asynchronous job processing with database and Redis backends

- **Task Scheduler** - Cron-based recurring jobs with enum presets

- **ORM Integration** - Jobs and failed jobs stored as entities with full ORM support

- **UUID Support** - Distributed-system-ready with RFC 4122 v4 UUIDs

- **Clock Integration** - Stores Clock objects for testable time operations

- **Attribute-Based** - Explicit `#[Serialize]` attribute for job properties

- **Failed Job Handling** - Complete retry, forget, flush, and prune commands

## Quick Start

### 1. Setup Database Tables

```bash
# Generate migrations
php bin/larafony table:jobs
php bin/larafony table:failed-jobs

# Run migrations
php bin/larafony migrate
```

### 2. Create a Job

```php
namespace App\Jobs;

use Larafony\Framework\Scheduler\Attributes\Serialize;
use Larafony\Framework\Scheduler\Job;

class SendWelcomeEmailJob extends Job
{
public function __construct(
#[Serialize] private int $userId,
#[Serialize] private string $email
) {}

public function handle(): void
{
// Send welcome email
$emailService = Application::instance()->get(EmailService::class);
$emailService->send($this->email, 'Welcome!', 'Welcome to our platform');
}

public function handleException(\Throwable $e): void
{
// Log the failure
error_log("Failed to send welcome email to {$this->email}: " . $e->getMessage());
}
}
```

> **Success:** **Explicit Serialization:** Only properties marked with `#[Serialize]` are serialized. This prevents accidental serialization of dependencies and makes the code more explicit and type-safe.

### 3. Dispatch the Job

```php
use Larafony\Framework\Scheduler\Dispatcher;

$dispatcher = $container->get(Dispatcher::class);

// Immediate dispatch
$jobId = $dispatcher->dispatch(new SendWelcomeEmailJob(123, 'user@example.com'));

// Delayed dispatch (after 5 minutes)
$jobId = $dispatcher->dispatchAfter(300, new SendWelcomeEmailJob(123, 'user@example.com'));

// Batch dispatch
$jobIds = $dispatcher->dispatchBatch(
new SendWelcomeEmailJob(1, 'user1@example.com'),
new SendWelcomeEmailJob(2, 'user2@example.com'),
new SendWelcomeEmailJob(3, 'user3@example.com')
);
```

### 4. Process Jobs

```bash
# Run worker continuously
php bin/larafony queue:work

# Process one job and exit (testing)
php bin/larafony queue:work --once

# Process max 100 jobs then exit (worker rotation)
php bin/larafony queue:work --max-jobs=100
```

## ORM Integration

Unlike other frameworks that use raw SQL, Larafony's queue system is fully ORM-based:

```php
// DatabaseQueue uses Job entity
public function push(JobContract $job): string
{
$jobEntity = new JobEntity();
$jobEntity->payload = serialize($job);
$jobEntity->queue = 'default';
$jobEntity->attempts = 0;
$jobEntity->reserved_at = null;
$jobEntity->available_at = ClockFactory::instance(); // Clock object!
$jobEntity->created_at = ClockFactory::instance();
$jobEntity->save();

return (string) $jobEntity->id; // Returns UUID
}
```

### Key Features

- **UUID Primary Keys** - Job entity has `use_uuid = true` for distributed systems

- **Clock Objects** - `available_at` and `created_at` are Clock instances, not DateTimeImmutable

- **ORM Queries** - Uses `JobEntity::query()` with proper type casting

- **OrderDirection Enum** - Type-safe sorting with `OrderDirection::ASC`

> **Info:** **Clock vs DateTimeImmutable:** Storing Clock objects enables seamless time mocking in tests with `ClockFactory::freeze()` and proper separation between system time and domain time.

## Task Scheduling

Schedule recurring jobs with cron-like syntax using enum presets:

### Configuration

```php
// config/schedule.php
use Larafony\Framework\Scheduler\CronSchedule;

return [
// Run every minute
HealthCheckJob::class => CronSchedule::EVERY_MINUTE,

// Run daily at 3:00 AM
DatabaseBackupJob::class => CronSchedule::DAILY->at(3, 0),

// Run every Monday at 9:00 AM
SendWeeklyReportJob::class => CronSchedule::MONDAY->at(9, 0),

// Run every 15 minutes
CleanupTempFilesJob::class => CronSchedule::EVERY_FIFTEEN_MINUTES,

// Run on weekdays at noon
SendDailyReportJob::class => CronSchedule::WEEKDAYS->at(12, 0),

// Custom cron expression
GenerateSitemapJob::class => '30 * * * *', // Every hour at :30

// Every N minutes
CacheWarmupJob::class => CronSchedule::everyNMinutes(10),
];
```

### Cron Setup

```bash
# Add this single cron entry (runs every minute)
* * * * * cd /var/www/project && php bin/larafony schedule:run >> /dev/null 2>&1
```

### Available Presets

- `EVERY_MINUTE` - Every minute

- `EVERY_FIVE_MINUTES` - Every 5 minutes

- `EVERY_FIFTEEN_MINUTES` - Every 15 minutes

- `EVERY_THIRTY_MINUTES` - Every 30 minutes

- `HOURLY` - Every hour at :00

- `DAILY` - Every day at midnight

- `WEEKLY` - Every Sunday at midnight

- `MONTHLY` - First day of month at midnight

- `MONDAY`, `TUESDAY`, ..., `SUNDAY` - Specific day at midnight

- `WEEKDAYS` - Monday-Friday at midnight

- `WEEKENDS` - Saturday-Sunday at midnight

## Failed Job Handling

When a job throws an exception, it's automatically logged to the `failed_jobs` table with full stack trace:

### List Failed Jobs

```bash
php bin/larafony queue:failed

# Output:
# UUID: 550e8400-e29b-41d4-a716-446655440000
# Queue: default
# Failed: 2024-01-15 14:30:22
# Exception: RuntimeException: Connection timeout...
```

### Retry Failed Jobs

```bash
# Retry specific job by UUID
php bin/larafony queue:retry 550e8400-e29b-41d4-a716-446655440000

# Retry all failed jobs
php bin/larafony queue:retry all
```

### Manage Failed Jobs

```bash
# Delete specific failed job
php bin/larafony queue:forget 550e8400-e29b-41d4-a716-446655440000

# Clear all failed jobs
php bin/larafony queue:flush

# Remove failed jobs older than 7 days
php bin/larafony queue:prune --hours=168
```

### Programmatic Access

```php
use Larafony\Framework\Scheduler\FailedJobRepository;

$failedJobRepo = $container->get(FailedJobRepository::class);

// Get all failed jobs
$failedJobs = $failedJobRepo->all();

// Retry and re-queue
$job = $failedJobRepo->retry('some-uuid-here');
if ($job) {
$dispatcher->dispatch($job);
}

// Prune old failures (older than 48 hours)
$count = $failedJobRepo->prune(48);

// Flush all
$failedJobRepo->flush();
```

## Testing with ClockFactory

Larafony's Clock system makes testing time-dependent queue behavior straightforward:

```php
use Larafony\Framework\Clock\ClockFactory;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
protected function setUp(): void
{
// Freeze time at a specific moment
ClockFactory::freeze(new \DateTimeImmutable('2024-01-01 12:00:00'));
}

protected function tearDown(): void
{
// Reset to real system time
ClockFactory::reset();
}

public function testDelayedJobIsNotAvailableImmediately(): void
{
$queue = new DatabaseQueue();

// Queue a job for 2 hours from now
$delay = new \DateTime('2024-01-01 14:00:00');
$jobId = $queue->later($delay, new SendEmailJob('test@example.com'));

// Job should not be available yet (current time is 12:00)
$this->assertNull($queue->pop());

// Advance time to 14:01
ClockFactory::freeze(new \DateTimeImmutable('2024-01-01 14:01:00'));

// Now job should be available
$job = $queue->pop();
$this->assertInstanceOf(SendEmailJob::class, $job);
}
}
```

> **Success:** **Testing Benefits:** Frozen time ensures deterministic tests, no need to wait for delays, easy edge case testing, and complete test isolation.

## Console Commands

### Queue Worker

```bash
# Basic usage (runs indefinitely)
php bin/larafony queue:work

# Process one job and exit
php bin/larafony queue:work --once

# Process specific queue
php bin/larafony queue:work --queue=emails

# Process max 100 jobs then exit
php bin/larafony queue:work --max-jobs=100

# Stop when queue is empty
php bin/larafony queue:work --stop-when-empty
```

### Schedule Runner

```bash
# Run scheduled tasks (call every minute via cron)
php bin/larafony schedule:run
```

### Migration Generators

```bash
# Generate jobs table migration
php bin/larafony table:jobs

# Generate failed_jobs table migration
php bin/larafony table:failed-jobs
```

## Production Setup

### Supervisor Configuration

```ini
[program:larafony-worker]
command=php /var/www/app/bin/larafony queue:work --stop-when-empty
autostart=true
autorestart=true
numprocs=3
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/app/storage/logs/worker.log
```

### Cron Configuration

```bash
# Schedule runner (every minute)
* * * * * cd /var/www/app && php bin/larafony schedule:run >> /dev/null 2>&1

# Clean up old failed jobs (daily at 3 AM)
0 3 * * * cd /var/www/app && php bin/larafony queue:prune --hours=168
```

## Database Schema

### Jobs Table

```sql
CREATE TABLE jobs (
id CHAR(36) PRIMARY KEY, -- UUID
payload TEXT NOT NULL, -- Serialized job
queue VARCHAR(255), -- Queue name
attempts INT DEFAULT 0, -- Retry counter
reserved_at DATETIME, -- Job lock
available_at DATETIME NOT NULL, -- When available
created_at DATETIME NOT NULL, -- Creation time
INDEX idx_queue_available (queue, available_at)
);
```

### Failed Jobs Table

```sql
CREATE TABLE failed_jobs (
id CHAR(36) PRIMARY KEY, -- UUID
uuid CHAR(36) UNIQUE NOT NULL, -- Unique identifier
connection VARCHAR(255) NOT NULL, -- Queue connection
queue VARCHAR(255) NOT NULL, -- Queue name
payload LONGTEXT NOT NULL, -- Serialized job
exception LONGTEXT NOT NULL, -- Stack trace
failed_at DATETIME NOT NULL -- Failure timestamp
);
```

## Comparison with Laravel

<table class="comparison-table table table-bordered table-striped">
<thead>
<tr>
<th>Feature</th>
<th>Larafony</th>
<th>Laravel
</thead>
<tbody>
<tr>
<td>Job Serialization</td>
<td>Explicit `#[Serialize]` attribute</td>
<td>Implicit (all constructor params)
<tr>
<td>Persistence</td>
<td>ORM-based with entities</td>
<td>Raw SQL queries
<tr>
<td>Primary Keys</td>
<td>UUID (distributed-ready)</td>
<td>Auto-increment integer
<tr>
<td>Time Handling</td>
<td>Clock objects (testable)</td>
<td>Carbon library
<tr>
<td>Cron Scheduling</td>
<td>Enum presets in config</td>
<td>Fluent API in code
<tr>
<td>Failed Jobs</td>
<td>UUID-based with ORM</td>
<td>ID-based with Horizon UI

> **Info:** **Larafony Advantage:** Explicit serialization prevents bugs, ORM provides type safety, UUIDs enable distributed systems, and Clock integration makes testing trivial.

## Best Practices

- **Keep Jobs Small** - One job should do one thing

- **Make Jobs Idempotent** - Jobs should be safe to run multiple times

- **Handle Failures Gracefully** - Implement `handleException()` method

- **Use Serializable Properties** - Mark constructor parameters with `#[Serialize]`

- **Monitor Failed Jobs** - Regularly check `queue:failed` output

- **Prune Old Failures** - Run `queue:prune` periodically

- **Use Appropriate Drivers** - Database for simplicity, Redis for performance

## Learn More

For detailed implementation examples, advanced patterns, and testing strategies, check out the complete tutorial at [masterphp.eu](https://masterphp.eu).
