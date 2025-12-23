---
title: "Authorization - Roles & Permissions"
description: "Built-in Role-Based Access Control (RBAC) system for fine-grained authorization"
---

# Authorization - Roles & Permissions

> **Info:** **Zero Dependencies:** Complete RBAC implementation built into the framework core—no external packages required. Users have Roles, Roles have Permissions.

## Overview

Larafony's authorization system provides:

- **Role-Based Access Control** - Users → Roles → Permissions hierarchy

- **ORM Integration** - BelongsToMany relationships with property hooks

- **Facade Pattern** - Convenient `Auth` static class for global access

- **Flexible Checks** - Single, any, or all role/permission verification

- **Database Driven** - Roles and permissions stored in database tables

- **Type-Safe** - Full PHP 8.5 type hints and property hooks

## Database Structure

The authorization system uses four tables:

```sql
-- Roles table
CREATE TABLE roles (
id BIGINT UNSIGNED PRIMARY KEY,
name VARCHAR(100) UNIQUE NOT NULL,
description VARCHAR(255) NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Permissions table
CREATE TABLE permissions (
id BIGINT UNSIGNED PRIMARY KEY,
name VARCHAR(100) UNIQUE NOT NULL,
description VARCHAR(255) NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Role-Permission pivot
CREATE TABLE role_permissions (
id BIGINT UNSIGNED PRIMARY KEY,
role_id BIGINT UNSIGNED NOT NULL,
permission_id BIGINT UNSIGNED NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
UNIQUE(role_id, permission_id),
INDEX(role_id),
INDEX(permission_id)
);

-- User-Role pivot
CREATE TABLE user_roles (
id BIGINT UNSIGNED PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL,
role_id BIGINT UNSIGNED NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
UNIQUE(user_id, role_id),
INDEX(user_id),
INDEX(role_id)
);
```

> **Success:** **Setup Commands:** Run `php bin/console database:init` to automatically create all auth tables.

## Creating Roles & Permissions

### Define Permissions

```php
use Larafony\Framework\Database\ORM\Entities\Permission;

// Create granular permissions
$createNotes = new Permission();
$createNotes->name = 'notes.create';
$createNotes->description = 'Can create notes';
$createNotes->save();

$editNotes = new Permission();
$editNotes->name = 'notes.edit';
$editNotes->description = 'Can edit notes';
$editNotes->save();

$deleteNotes = new Permission();
$deleteNotes->name = 'notes.delete';
$deleteNotes->description = 'Can delete notes';
$deleteNotes->save();
```

### Create Roles with Permissions

```php
use Larafony\Framework\Database\ORM\Entities\Role;

// Create admin role
$adminRole = new Role();
$adminRole->name = 'admin';
$adminRole->description = 'Administrator with full access';
$adminRole->save();

// Attach all permissions to admin role
$adminRole->relations->getRelationInstance('permissions')
 ->attach([
$createNotes->id,
$editNotes->id,
$deleteNotes->id
]);

// Create editor role with limited permissions
$editorRole = new Role();
$editorRole->name = 'editor';
$editorRole->description = 'Can create and edit content';
$editorRole->save();

$editorRole->relations->getRelationInstance('permissions')
 ->attach([
$createNotes->id,
$editNotes->id
// No delete permission
]);
```

### Assign Roles to Users

```php
use App\Models\User;

// Fetch user
$user = User::query()->where('email', '=', 'john@example.com')->first();

// Add role (prevents duplicates automatically)
$user->addRole($adminRole);

// User now has all permissions from admin role
```

## Checking Permissions

### In Controllers

```php
use Larafony\Framework\Auth\Auth;
use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;

class NoteController extends Controller
{
public function create(): ResponseInterface
{
// Check if user has specific permission
if (!Auth::hasPermission('notes.create')) {
return $this->json([
'message' => 'Forbidden',
'errors' => ['permission' => [
'You do not have permission to create notes.'
]]
], 403);
}

// User has permission, proceed
return $this->render('notes.create');
}

public function delete(int $id): ResponseInterface
{
// Check if user has ANY of these permissions
if (!Auth::hasAnyPermission(['notes.delete', 'admin.all'])) {
return $this->json(['message' => 'Forbidden'], 403);
}

// Delete the note
Note::query()->where('id', '=', $id)->delete();

return $this->json(['message' => 'Note deleted']);
}
}
```

### Checking Roles

```php
use Larafony\Framework\Auth\Auth;

// Check single role
if (Auth::hasRole('admin')) {
// User is an admin
}

// Check if user has ANY of these roles
if (Auth::hasAnyRole(['admin', 'moderator'])) {
// User is either admin OR moderator
}

// Check if user has ALL these roles
if (Auth::hasAllRoles(['admin', 'super-user'])) {
// User has BOTH admin AND super-user roles
}
```

### Multiple Permission Checks

```php
// Check if user has ALL specified permissions
if (Auth::hasAllPermissions(['notes.create', 'notes.edit', 'notes.delete'])) {
// User has full CRUD permissions
}

// Check if user has ANY specified permission
if (Auth::hasAnyPermission(['notes.edit', 'notes.delete'])) {
// User can modify or delete notes
}
```

## Direct Model Usage

### User Model

```php
$user = Auth::user();

// Check roles directly on user
if ($user->hasRole('admin')) {
echo "User is an admin";
}

// Check permissions (checks through all user's roles)
if ($user->hasPermission('notes.delete')) {
echo "User can delete notes";
}

// Access all user's roles (PHP 8.5 property hooks)
foreach ($user->roles as $role) {
echo $role->name . "\n";

// Check if role has specific permission
if ($role->hasPermission('notes.create')) {
echo "Role can create notes\n";
}
}
```

### Role Model

```php
$role = Role::query()->where('name', '=', 'editor')->first();

// Check if role has permission
if ($role->hasPermission('posts.create')) {
echo "Editors can create posts";
}

// Access all permissions for this role
foreach ($role->permissions as $permission) {
echo $permission->name . "\n";
}

// Access all users with this role
foreach ($role->users as $user) {
echo $user->email . " is an editor\n";
}
```

## Architecture Components

### Auth Facade

The `Auth` class provides unified access to authentication and authorization:

```php
namespace Larafony\Framework\Auth;

final class Auth
{
// Authentication methods
public static function attempt(User $user, string $password, bool $remember = false): bool
public static function login(User $user, bool $remember = false): void
public static function logout(): void
public static function user(): ?User
public static function check(): bool
public static function guest(): bool
public static function id(): int|string|null

// Role authorization methods
public static function hasRole(string $role): bool
public static function hasAnyRole(array $roles): bool
public static function hasAllRoles(array $roles): bool

// Permission authorization methods
public static function hasPermission(string $permission): bool
public static function hasAnyPermission(array $permissions): bool
public static function hasAllPermissions(array $permissions): bool
}
```

### RoleManager

Handles role-based authorization checks:

```php
namespace Larafony\Framework\Auth;

final readonly class RoleManager
{
public function __construct(private UserManager $userManager) {}

public function hasRole(string $role): bool
{
return $this->userManager->check()
&& $this->userManager->user()?->hasRole($role);
}

public function hasAnyRole(array $roles): bool
public function hasAllRoles(array $roles): bool
}
```

### PermissionManager

Handles permission-based authorization checks:

```php
namespace Larafony\Framework\Auth;

final readonly class PermissionManager
{
public function __construct(private UserManager $userManager) {}

public function hasPermission(string $permission): bool
{
return $this->userManager->check()
&& $this->userManager->user()?->hasPermission($permission);
}

public function hasAnyPermission(array $permissions): bool
public function hasAllPermissions(array $permissions): bool
}
```

## ORM Entities

### User Entity

```php
namespace Larafony\Framework\Database\ORM\Entities;

use Larafony\Framework\Database\ORM\Attributes\BelongsToMany;
use Larafony\Framework\Database\ORM\Model;

class User extends Model
{
// Many-to-many relationship to roles
#[BelongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')]
public array $roles {
get => $this->relations->getRelation('roles');
}

// Add role to user (prevents duplicates)
public function addRole(Role $role): void
{
if ($this->hasRole($role->name)) {
return;
}
$this->relations->getRelationInstance('roles')
->attach([$role->id]);
}

// Check if user has role by name
public function hasRole(string $roleName): bool
{
return array_any(
$this->roles,
static fn(Role $role) => $role->name === $roleName
);
}

// Check if user has permission through roles
public function hasPermission(string $permissionName): bool
{
return array_any(
$this->roles,
static fn(Role $role) => $role->hasPermission($permissionName)
);
}
}
```

### Role Entity

```php
namespace Larafony\Framework\Database\ORM\Entities;

use Larafony\Framework\Database\ORM\Attributes\BelongsToMany;
use Larafony\Framework\Database\ORM\Model;

class Role extends Model
{
public string $name { get => $this->name; set { /* ... */ } }
public ?string $description { get => $this->description; set { /* ... */ } }

// Many-to-many to permissions
#[BelongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')]
public array $permissions {
get => $this->relations->getRelation('permissions');
}

// Many-to-many to users
#[BelongsToMany(User::class, 'user_roles', 'role_id', 'user_id')]
public array $users {
get => $this->relations->getRelation('users');
}

// Check if role has specific permission
public function hasPermission(string $permissionName): bool
{
return in_array(
$permissionName,
array_column($this->permissions, 'name')
);
}
}
```

### Permission Entity

```php
namespace Larafony\Framework\Database\ORM\Entities;

use Larafony\Framework\Database\ORM\Attributes\BelongsToMany;
use Larafony\Framework\Database\ORM\Model;

class Permission extends Model
{
public string $name { get => $this->name; set { /* ... */ } }
public ?string $description { get => $this->description; set { /* ... */ } }

// Inverse many-to-many to roles
#[BelongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id')]
public array $roles {
get => $this->relations->getRelation('roles');
}
}
```

## Permission Naming Convention

Use dot notation for hierarchical permission structure:

**Format:** `resource.action`

- `notes.create` - Can create notes

- `notes.edit` - Can edit notes

- `notes.delete` - Can delete notes

- `users.manage` - Can manage users

- `admin.all` - Full admin access

## Security Best Practices

> **Warning:** **Always Check Authentication First:** Permission checks assume user is authenticated. Always verify with `Auth::check()` before checking permissions.

```php
// ✅ CORRECT - Check authentication first
if (!Auth::check()) {
return $this->json(['message' => 'Unauthorized'], 401);
}

if (!Auth::hasPermission('notes.create')) {
return $this->json(['message' => 'Forbidden'], 403);
}

// ❌ INCORRECT - Permission check on unauthenticated user
if (!Auth::hasPermission('notes.create')) {
// This will return false for guests, but it's unclear why
}
```

### HTTP Status Codes

- **401 Unauthorized** - User is not authenticated (not logged in)

- **403 Forbidden** - User is authenticated but lacks permission

## Comparison with Other Frameworks

<table class="table table-dark table-bordered">
<thead>
<tr>
<th>Feature</th>
<th>Larafony</th>
<th>Laravel + Spatie</th>
<th>Symfony
</thead>
<tbody>
<tr>
<td>**Integration**</td>
<td>Built into core</td>
<td>External package required</td>
<td>Built-in voters
<tr>
<td>**User-Permission**</td>
<td>Only through roles (pure RBAC)</td>
<td>Direct + through roles</td>
<td>Through voters
<tr>
<td>**API Style**</td>
<td>`Auth::hasPermission('x')`</td>
<td>`$user->can('x')`</td>
<td>`isGranted('x')`
<tr>
<td>**Tables**</td>
<td>4 tables</td>
<td>5 tables</td>
<td>Configurable
<tr>
<td>**Wildcards**</td>
<td>Not supported</td>
<td>Supported (`posts.*`)</td>
<td>Custom voters
<tr>
<td>**Caching**</td>
<td>Manual</td>
<td>Built-in</td>
<td>Built-in

## Real World Example

Complete authorization flow from the demo application:

```php
// 1. Create permissions in seeder
$createNotePermission = new Permission();
$createNotePermission->name = 'notes.create';
$createNotePermission->description = 'Can create notes';
$createNotePermission->save();

// 2. Create role
$adminRole = new Role();
$adminRole->name = 'admin';
$adminRole->description = 'Administrator role';
$adminRole->save();

// 3. Attach permission to role (not in demo, but should be)
$adminRole->relations->getRelationInstance('permissions')
 ->attach([$createNotePermission->id]);

// 4. Assign role to user
$user = new User();
$user->email = 'admin@example.com';
$user->password = 'password'; // Auto-hashed with Argon2id
$user->save();
$user->addRole($adminRole);

// 5. Check permission in controller
class NoteController extends Controller
{
public function store(CreateNoteDto $dto): ResponseInterface
{
if (!Auth::check()) {
return $this->json(['message' => 'Unauthorized'], 401);
}

if (!Auth::hasPermission('notes.create')) {
return $this->json(['message' => 'Forbidden'], 403);
}

// Create note...
$note = new Note()->fill([
'title' => $dto->title,
'content' => $dto->content,
'user_id' => Auth::user()->id,
]);
$note->save();

return $this->redirect('/notes');
}
}
```

> **Success:** **Pro Tip:** Create seeders for roles and permissions in your application to ensure consistent authorization setup across environments.
