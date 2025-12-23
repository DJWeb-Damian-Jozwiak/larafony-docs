---
title: "Schema Builder & Migrations"
description: "Build and modify database schemas with Larafony\'s fluent Schema Builder and migration system."
---

# Schema Builder & Migrations

> **Info:** **SQL Transparency:** Schema Builder returns SQL strings for inspection before execution - you're always in control.

## Overview

The Schema Builder provides an expressive, database-agnostic API for creating and modifying database schemas.
Key features include:

- **Fluent API** - Chain methods to define tables and columns

- **Type-safe** - PHP 8.5 enums and property hooks ensure correctness

- **SQL Inspection** - See generated SQL before execution

- **Migration System** - Version control your database schema

- **Pipe Operator** - Modern PHP 8.5 syntax for cleaner code

## Basic Table Creation

### Simple Table

```php
use Larafony\Framework\Database\Schema;

// Create a users table
$sql = Schema::create('users', function ($table) {
$table->id(); // Auto-increment primary key
$table->string('name'); // VARCHAR(255)
$table->string('email');
$table->timestamps(); // created_at, updated_at
});

// Inspect the SQL
echo $sql;

// Execute when ready
Schema::execute($sql);
```

### Column Types

```php
$sql = Schema::create('products', function ($table) {
// Primary key
$table->id();

// String types
$table->string('name', 200); // VARCHAR(200)
$table->text('description'); // TEXT
$table->char('code', 10); // CHAR(10)

// Numeric types
$table->integer('stock'); // INT(11)
$table->bigInteger('views'); // BIGINT
$table->decimal('price', 8, 2); // DECIMAL(8,2)
$table->float('rating'); // FLOAT

// Date and time
$table->date('released_at'); // DATE
$table->datetime('expires_at'); // DATETIME
$table->timestamp('verified_at'); // TIMESTAMP
$table->timestamps(); // created_at, updated_at

// Boolean
$table->boolean('is_active'); // TINYINT(1)

// Enum
$table->enum('status', ['draft', 'published', 'archived']);

// JSON
$table->json('metadata'); // JSON
});
```

### Column Modifiers

```php
$sql = Schema::create('orders', function ($table) {
$table->id();

// Nullable columns
$table->string('notes')->nullable(true);

// Non-nullable (default)
$table->string('customer_name')->nullable(false);

// Default values
$table->integer('status')->default(1);
$table->boolean('is_paid')->default(false);
$table->timestamp('created_at')->default('CURRENT_TIMESTAMP');

// Unique constraint
$table->string('order_number')->nullable(false)->unique();

// Chaining modifiers
$table->string('email')
->nullable(false)
->unique();
});
```

## Indexes

### Adding Indexes

```php
$sql = Schema::create('posts', function ($table) {
$table->id();
$table->string('title');
$table->string('slug');
$table->integer('user_id');
$table->integer('category_id');
$table->timestamp('published_at')->nullable(true);

// Primary key (automatic with id())
// $table->id() adds PRIMARY KEY automatically

// Unique index
$table->unique('slug');

// Regular index (single column)
$table->index('user_id');

// Composite index (multiple columns)
$table->index(['published_at', 'category_id']);

// Named index
$table->index('user_id', 'idx_posts_user');
});
```

## Table Modifications

### Adding Columns

```php
// Add new columns to existing table
$sql = Schema::table('users', function ($table) {
$table->string('phone', 20)->nullable(true);
$table->date('birth_date')->nullable(true);
$table->integer('status')->default(1);
});

Schema::execute($sql);
```

### Modifying Columns

```php
// Change column properties
$sql = Schema::table('users', function ($table) {
// Make column non-nullable
$table->change('email')->nullable(false);

// Change default value
$table->change('status')->default(2);
});

Schema::execute($sql);
```

### Dropping Columns

```php
// Remove columns from table
$sql = Schema::table('users', function ($table) {
$table->drop('phone');
$table->drop('status');
});

Schema::execute($sql);
```

## Migrations with Pipe Operator

### Migration Structure

Migrations are stored in `database/migrations/` with numeric prefixes for ordering:

```php
<?php
// database/migrations/001_create_users_table.php

declare(strict_types=1);

use Larafony\Framework\Database\Base\Migrations\Migration;
use Larafony\Framework\Database\Schema;

return new class extends Migration
{
public function up(): void
{
$sql = Schema::create('users', function ($table) {
$table->id();
$table->string('name');
$table->string('email');
$table->unique('email');
$table->timestamps();
});

Schema::execute($sql);
}

public function down(): void
{
Schema::dropIfExists('users');
}
};
```

### Using Pipe Operator in Migrations

PHP 8.5's pipe operator (`|&gt;`) allows elegant functional composition in migrations:

```php
<?php
// database/migrations/002_create_posts_table.php

use Larafony\Framework\Database\Schema;

return new class extends Migration
{
public function up(): void
{
// Traditional approach
$sql = Schema::create('posts', function ($table) {
$table->id();
$table->string('title');
$table->text('content');
$table->integer('user_id');
$table->timestamps();
});
Schema::execute($sql);

// With pipe operator - cleaner composition
Schema::create('posts', function ($table) {
$table->id();
$table->string('title');
$table->text('content');
$table->integer('user_id');
$table->timestamps();
}) |> Schema::execute(...);
}

public function down(): void
{
Schema::dropIfExists('posts');
}
};
```

### Advanced Pipe Operator Usage

```php
// Chain multiple operations with pipe operator
Schema::create('comments', fn($t) =>
$t->id(),
$t->integer('post_id'),
$t->text('content'),
$t->timestamps()
)
|> Schema::execute(...)
|> fn() => Schema::create('comment_votes', fn($t) =>
$t->id(),
$t->integer('comment_id'),
$t->integer('user_id'),
$t->enum('type', ['up', 'down'])
)
|> Schema::execute(...);
```

> **Success:** **Tip:** The pipe operator (`|&gt;`) passes the result of the left expression as the first argument to the right expression, enabling functional-style programming in PHP 8.5.

## Running Migrations

### Console Command

```bash
# Run all pending migrations
php bin/larafony migrate

# Rollback last migration batch
php bin/larafony migrate:rollback

# Reset database (rollback all)
php bin/larafony migrate:reset

# Refresh database (reset + migrate)
php bin/larafony migrate:refresh
```

## Practical Examples

### Example 1: Blog Schema

```php
// Create posts table
Schema::create('posts', function ($table) {
$table->id();
$table->integer('user_id')->nullable(false);
$table->integer('category_id')->nullable(true);
$table->string('title', 200)->nullable(false);
$table->string('slug')->nullable(false);
$table->text('content');
$table->text('excerpt')->nullable(true);
$table->string('featured_image')->nullable(true);
$table->enum('status', ['draft', 'published', 'archived'])->default('draft');
$table->timestamp('published_at')->nullable(true);
$table->integer('views')->default(0);
$table->timestamps();
$table->softDeletes(); // deleted_at timestamp

// Indexes
$table->unique('slug');
$table->index('user_id');
$table->index('category_id');
$table->index(['status', 'published_at']);
}) |> Schema::execute(...);
```

### Example 2: E-commerce Schema

```php
// Products table
Schema::create('products', function ($table) {
$table->id();
$table->string('sku', 50)->nullable(false);
$table->string('name');
$table->text('description')->nullable(true);
$table->decimal('price', 10, 2)->nullable(false);
$table->decimal('compare_price', 10, 2)->nullable(true);
$table->integer('stock')->default(0);
$table->boolean('is_active')->default(true);
$table->json('attributes')->nullable(true); // Color, size, etc.
$table->timestamps();

$table->unique('sku');
$table->index('is_active');
}) |> Schema::execute(...);

// Orders table
Schema::create('orders', function ($table) {
$table->id();
$table->string('order_number', 20)->nullable(false);
$table->integer('user_id')->nullable(false);
$table->decimal('total', 10, 2)->nullable(false);
$table->enum('status', ['pending', 'paid', 'shipped', 'delivered', 'cancelled'])
->default('pending');
$table->timestamp('paid_at')->nullable(true);
$table->timestamp('shipped_at')->nullable(true);
$table->timestamp('delivered_at')->nullable(true);
$table->timestamps();

$table->unique('order_number');
$table->index('user_id');
$table->index(['status', 'created_at']);
}) |> Schema::execute(...);
```

### Example 3: Pivot Table (Many-to-Many)

```php
// Note-Tag pivot table
Schema::create('note_tag', function ($table) {
$table->id();
$table->integer('note_id')->nullable(false);
$table->integer('tag_id')->nullable(false);
$table->timestamps();

// Composite index for relationship queries
$table->index(['note_id', 'tag_id']);

// Prevent duplicate relationships
$table->unique(['note_id', 'tag_id']);
}) |> Schema::execute(...);
```

## Best Practices

#### Do

- Always inspect generated SQL before executing in production

- Use the pipe operator for cleaner migration code

- Add indexes on foreign key columns

- Use meaningful migration file names with numeric prefixes

- Implement both up() and down() methods

- Test migrations on a copy of production data

#### Don't

- Don't modify published migrations - create new ones instead

- Don't forget to add indexes on frequently queried columns

- Don't use nullable() on primary or foreign keys

- Don't execute migrations directly in production without testing

## API Reference

### Schema Facade Methods

<table class="table table-dark table-bordered">
<thead>
<tr>
<th>Method</th>
<th>Description
</thead>
<tbody>
<tr>
<td>`Schema::create(string $table, Closure $callback)`</td>
<td>Create a new table
<tr>
<td>`Schema::table(string $table, Closure $callback)`</td>
<td>Modify existing table
<tr>
<td>`Schema::drop(string $table)`</td>
<td>Drop a table
<tr>
<td>`Schema::dropIfExists(string $table)`</td>
<td>Drop table if it exists
<tr>
<td>`Schema::execute(string $sql)`</td>
<td>Execute SQL statement
<tr>
<td>`Schema::hasTable(string $table)`</td>
<td>Check if table exists
<tr>
<td>`Schema::getColumnListing(string $table)`</td>
<td>Get list of columns

## Next Steps

#### Query Builder

Learn how to query your database with the fluent Query Builder.

[
Read Guide 
](/docs/query-builder)

#### ORM Models

Explore the Active Record ORM with attribute-based relationships.

[
Read Guide 
](/docs/models)
