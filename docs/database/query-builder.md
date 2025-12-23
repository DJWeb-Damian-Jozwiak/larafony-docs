---
title: "Query Builder"
description: "Build and execute SQL queries with Larafony\'s fluent, type-safe Query Builder."
---

# Query Builder

> **Info:** **SQL Injection Protection:** All queries use prepared statements with automatic parameter binding.

## Overview

The Query Builder provides an expressive, chainable API for constructing SQL queries programmatically.
Key features include:

- **Fluent Interface** - Chain methods to build complex queries

- **SQL Injection Protection** - Automatic parameter binding

- **Type Safety** - PHP 8.5 enums for query types and directions

- **Database Agnostic** - Works across MySQL, PostgreSQL, SQLite

## Basic SELECT Queries

### Simple SELECT

```php
use Larafony\Framework\Database\DatabaseManager;

$db = app(DatabaseManager::class);

// Select all columns
$users = $db->table('users')->get();

// Select specific columns
$users = $db->table('users')
 ->select(['id', 'name', 'email'])
 ->get();
```

### Get Single Record

```php
// Get first matching record
$user = $db->table('users')
 ->where('email', '=', 'john@example.com')
 ->first(); // Returns array or null
```

## WHERE Clauses

### Basic WHERE

```php
// Simple where conditions
$users = $db->table('users')
 ->where('status', '=', 'active')
 ->where('age', '>', 18)
 ->get();
// SQL: SELECT * FROM users WHERE status = ? AND age > ?
```

### OR WHERE

```php
$users = $db->table('users')
 ->where('status', '=', 'active')
 ->orWhere('verified', '=', true)
 ->get();
// SQL: SELECT * FROM users WHERE status = ? OR verified = ?
```

### WHERE IN

```php
$users = $db->table('users')
 ->whereIn('id', [1, 2, 3, 4, 5])
 ->get();
// SQL: SELECT * FROM users WHERE id IN (?, ?, ?, ?, ?)
```

### WHERE NOT IN

```php
$users = $db->table('users')
 ->whereNotIn('status', ['banned', 'deleted'])
 ->get();
```

### WHERE NULL / NOT NULL

```php
// Where column is NULL
$users = $db->table('users')
 ->whereNull('deleted_at')
 ->get();

// Where column is NOT NULL
$users = $db->table('users')
 ->whereNotNull('email_verified_at')
 ->get();
```

### WHERE BETWEEN

```php
$users = $db->table('users')
 ->whereBetween('age', [18, 65])
 ->get();
// SQL: SELECT * FROM users WHERE age BETWEEN ? AND ?
```

### WHERE LIKE

```php
$users = $db->table('users')
 ->whereLike('name', '%John%')
 ->get();
// SQL: SELECT * FROM users WHERE name LIKE ?
```

### Nested WHERE (Grouping)

```php
$users = $db->table('users')
 ->where('status', '=', 'active')
 ->whereNested(function ($query) {
$query->where('age', '>', 18)
->orWhere('verified', '=', true);
}, 'and')
 ->get();
// SQL: SELECT * FROM users
// WHERE status = ? AND (age > ? OR verified = ?)
```

## JOINs

### LEFT JOIN

```php
$results = $db->table('users')
 ->select(['users.*', 'profiles.bio', 'profiles.avatar'])
 ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
 ->where('users.status', '=', 'active')
 ->get();
```

### INNER JOIN

```php
use Larafony\Framework\Database\Base\Query\Enums\JoinType;

$results = $db->table('orders')
 ->select(['orders.*', 'users.name'])
 ->join('users', 'orders.user_id', '=', 'users.id', JoinType::INNER)
 ->get();
```

### Multiple JOINs

```php
$results = $db->table('orders')
 ->select(['orders.*', 'users.name', 'products.title'])
 ->leftJoin('users', 'orders.user_id', '=', 'users.id')
 ->leftJoin('products', 'orders.product_id', '=', 'products.id')
 ->get();
```

## Ordering and Limiting

### ORDER BY

```php
use Larafony\Framework\Database\Base\Query\Enums\OrderDirection;

// Order by single column
$users = $db->table('users')
 ->orderBy('name', OrderDirection::ASC)
 ->get();

// Multiple order clauses
$users = $db->table('users')
 ->orderBy('created_at', OrderDirection::DESC)
 ->orderBy('name', OrderDirection::ASC)
 ->get();
```

### Convenience Methods

```php
// Order by created_at DESC
$users = $db->table('users')->latest()->get();

// Order by created_at ASC
$users = $db->table('users')->oldest()->get();

// Custom column
$users = $db->table('users')->latest('updated_at')->get();
```

### LIMIT and OFFSET

```php
// Pagination - page 2, 20 per page
$users = $db->table('users')
 ->limit(20)
 ->offset(20)
 ->get();

// First 10 records
$users = $db->table('users')
 ->limit(10)
 ->get();
```

## Aggregates

### COUNT

```php
// Count all records
$total = $db->table('users')->count();

// Count with conditions
$activeUsers = $db->table('users')
 ->where('status', '=', 'active')
 ->count();
```

## INSERT Operations

### Insert Single Record

```php
$success = $db->table('users')->insert([
'name' => 'John Doe',
'email' => 'john@example.com',
'status' => 'active',
'created_at' => date('Y-m-d H:i:s')
]);
// Returns: true on success
```

### Insert and Get ID

```php
$userId = $db->table('users')->insertGetId([
'name' => 'Jane Smith',
'email' => 'jane@example.com'
]);
// Returns: last insert ID as string
```

## UPDATE Operations

```php
// Update records matching conditions
$affectedRows = $db->table('users')
 ->where('id', '=', 1)
 ->update([
'status' => 'inactive',
'updated_at' => date('Y-m-d H:i:s')
]);
// Returns: number of affected rows

// Update multiple records
$affectedRows = $db->table('users')
 ->where('status', '=', 'pending')
 ->whereNull('email_verified_at')
 ->update(['status' => 'inactive']);
```

## DELETE Operations

```php
// Delete specific record
$deletedRows = $db->table('users')
 ->where('id', '=', 1)
 ->delete();

// Delete with multiple conditions
$deletedRows = $db->table('users')
 ->where('status', '=', 'inactive')
 ->whereNull('last_login_at')
 ->delete();
// Returns: number of deleted rows
```

## Query Debugging

### Inspect SQL

```php
// Get SQL with placeholders
$sql = $db->table('users')
 ->where('status', '=', 'active')
 ->toSql();
// Returns: "SELECT * FROM users WHERE status = ?"

// Get SQL with actual values (safe for debugging!)
$rawSql = $db->table('users')
 ->where('status', '=', 'active')
 ->toRawSql();
// Returns: "SELECT * FROM users WHERE status = 'active'"
```

> **Info:** **Safe Debugging:** The `toRawSql()` method is inspired by [Laravel PR #47507](https://github.com/laravel/framework/pull/47507) and uses proper SQL escaping to safely display queries with their actual values for debugging purposes.

### How toRawSql() Works

Unlike simple string concatenation, `toRawSql()` properly escapes values to prevent SQL injection
when displaying queries for debugging:

```php
// Complex query with multiple types
$rawSql = $db->table('users')
 ->where('name', '=', "O'Brien") // String with quote
 ->where('age', '>', 18) // Integer
 ->whereNull('deleted_at') // NULL
 ->whereBetween('score', [10, 100])
 ->toRawSql();

// Safely escaped output:
// SELECT * FROM users
// WHERE name = 'O\'Brien'
// AND age > 18
// AND deleted_at IS NULL
// AND score BETWEEN 10 AND 100
```

### Best Use Cases for toRawSql()

- **Debugging slow queries** - Copy the output to MySQL Workbench or phpMyAdmin

- **Logging queries** - Log the complete SQL for troubleshooting

- **Development** - Understand what SQL is being generated

- **Testing** - Verify query structure in unit tests

```php
// Example: Log slow queries
$startTime = microtime(true);
$results = $query->get();
$duration = microtime(true) - $startTime;

if ($duration > 1.0) {
Log::warning('Slow query detected', [
'sql' => $query->toRawSql(),
'duration' => $duration
]);
}
```

> **Warning:** **Important:** While `toRawSql()` safely escapes values for display, never use it to execute queries. Always use the query builder's `get()`, `insert()`, `update()`, or `delete()` methods which use prepared statements for true SQL injection protection.

## Complex Query Examples

### Example 1: Active Users with Orders

```php
$results = $db->table('users')
 ->select(['users.id', 'users.name', 'COUNT(orders.id) as order_count'])
 ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
 ->where('users.status', '=', 'active')
 ->whereNotNull('users.email_verified_at')
 ->orderBy('order_count', OrderDirection::DESC)
 ->limit(10)
 ->get();
```

### Example 2: Product Search

```php
$products = $db->table('products')
 ->select(['products.*', 'categories.name as category_name'])
 ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
 ->where('products.is_active', '=', true)
 ->whereNested(function ($query) use ($searchTerm) {
$query->whereLike('products.name', "%{$searchTerm}%")
->orWhereLike('products.description', "%{$searchTerm}%");
}, 'and')
 ->whereBetween('products.price', [10, 100])
 ->orderBy('products.created_at', OrderDirection::DESC)
 ->limit(20)
 ->get();
```

### Example 3: User Activity Report

```php
$report = $db->table('users')
 ->select([
'users.id',
'users.name',
'users.email',
'COUNT(DISTINCT orders.id) as total_orders',
'SUM(orders.total) as total_spent'
])
 ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
 ->where('users.created_at', '>=', '2024-01-01')
 ->whereNotNull('users.email_verified_at')
 ->orderBy('total_spent', OrderDirection::DESC)
 ->limit(50)
 ->get();
```

## Best Practices

#### Do

- Always use query builder methods instead of raw SQL

- Add appropriate indexes for columns used in WHERE and JOIN clauses

- Use `first()` when you only need one record

- Use `select()` to limit columns and reduce data transfer

- Use `count()` instead of `get()` when you only need the count

#### Don't

- Don't use `toRawSql()` for production queries

- Don't build WHERE clauses with string concatenation

- Don't forget to add WHERE clauses to UPDATE and DELETE operations

- Don't use SELECT * in production - specify columns

## API Reference

<table class="table table-dark table-bordered">
<thead>
<tr>
<th>Method</th>
<th>Description
</thead>
<tbody>
<tr>
<td>`table(string $table)`</td>
<td>Set the table for query
<tr>
<td>`select(array $columns)`</td>
<td>Specify columns to select
<tr>
<td>`where($column, $operator, $value)`</td>
<td>Add WHERE clause
<tr>
<td>`orWhere($column, $operator, $value)`</td>
<td>Add OR WHERE clause
<tr>
<td>`whereIn($column, array $values)`</td>
<td>WHERE IN clause
<tr>
<td>`whereNull(string $column)`</td>
<td>WHERE column IS NULL
<tr>
<td>`join($table, $first, $op, $second)`</td>
<td>Add JOIN clause
<tr>
<td>`orderBy($column, $direction)`</td>
<td>Add ORDER BY clause
<tr>
<td>`limit(int $value)`</td>
<td>Add LIMIT clause
<tr>
<td>`get()`</td>
<td>Execute and get results
<tr>
<td>`first()`</td>
<td>Get first result or null
<tr>
<td>`count()`</td>
<td>Count matching records
<tr>
<td>`insert(array $values)`</td>
<td>Insert record
<tr>
<td>`update(array $values)`</td>
<td>Update records
<tr>
<td>`delete()`</td>
<td>Delete records

## Next Steps

#### ORM Models

Learn about Active Record models with attribute-based relationships.

[
Read Guide 
](/docs/models)

#### Schema Builder

Create and modify database schemas with migrations.

[
Read Guide 
](/docs/schema-builder)
