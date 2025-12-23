---
title: "Models & Relationships"
description: "Create ORM models with attribute-based relationships using PHP 8.5"
---

# Models & Relationships

## Creating a Model

Models in Larafony extend the `Model` base class and use PHP 8.5 property hooks for clean, type-safe data access.

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Larafony\Framework\Database\ORM\Model;

class User extends Model
{
public string $table { get => 'users'; }

public array $fillable = ['name', 'email'];

public ?string $name {
get => $this->name ?? null;
set {
$this->name = $value;
$this->markPropertyAsChanged('name');
}
}

public ?string $email {
get => $this->email ?? null;
set {
$this->email = $value;
$this->markPropertyAsChanged('email');
}
}
}
```

> **Info:** **Property Hooks:** PHP 8.5 property hooks allow you to define getter and setter logic directly on properties. Call `markPropertyAsChanged()` in setters to track changes for saving.

## BelongsTo Relationship

A `BelongsTo` relationship defines that a model belongs to another model.
For example, a `Note` belongs to a `User`.

```php
<?php

namespace App\Models;

use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Attributes\BelongsTo;

class Note extends Model
{
public string $table { get => 'notes'; }

public array $fillable = ['title', 'content', 'user_id'];

// BelongsTo: Note belongs to User
#[BelongsTo(
related: User::class,
foreign_key: 'user_id',
local_key: 'id'
)]
public ?User $user {
get => $this->relations->getRelation('user');
}
}
```

Access the relationship like a property:

```php
$note = Note::query()->find(1);
echo $note->user->name; // Lazy-loaded automatically
```

## HasMany Relationship

A `HasMany` relationship defines that a model has many related models.
For example, a `User` has many `Note`s.

```php
<?php

namespace App\Models;

use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Attributes\HasMany;

class User extends Model
{
public string $table { get => 'users'; }

// HasMany: User has many Notes
#[HasMany(
related: Note::class,
foreign_key: 'user_id',
local_key: 'id'
)]
public array $notes {
get => $this->relations->getRelation('notes');
}
}
```

Access the collection:

```php
$user = User::query()->find(1);

foreach ($user->notes as $note) {
echo $note->title;
}
```

## BelongsToMany Relationship

A `BelongsToMany` relationship defines a many-to-many relationship through a pivot table.
For example, a `Note` belongs to many `Tag`s, and a `Tag` belongs to many `Note`s.

```php
<?php

namespace App\Models;

use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Attributes\BelongsToMany;

class Note extends Model
{
public string $table { get => 'notes'; }

// BelongsToMany: Note belongs to many Tags
#[BelongsToMany(
related: Tag::class,
pivot_table: 'note_tag',
foreign_pivot_key: 'note_id',
related_pivot_key: 'tag_id'
)]
public array $tags {
get => $this->relations->getRelation('tags');
}

/**
* Attach tags to this note
*/
public function attachTags(array $tagIds): void
{
$relation = $this->relations->getRelationInstance('tags');
$relation->attach($tagIds);
}

/**
* Detach tags from this note
*/
public function detachTags(array $tagIds): void
{
$relation = $this->relations->getRelationInstance('tags');
$relation->detach($tagIds);
}
}
```

Working with many-to-many relationships:

```php
$note = Note::query()->find(1);

// Access tags
foreach ($note->tags as $tag) {
echo $tag->name;
}

// Attach new tags
$note->attachTags([1, 2, 3]);

// Detach tags
$note->detachTags([2]);
```

## Complete Example

Here's a complete model with all three relationship types:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Attributes\{
BelongsTo,
HasMany,
BelongsToMany
};

class Note extends Model
{
public string $table { get => 'notes'; }

public array $fillable = ['title', 'content', 'user_id'];

public ?string $title {
get => $this->title ?? null;
set {
$this->title = $value;
$this->markPropertyAsChanged('title');
}
}

public ?string $content {
get => $this->content ?? null;
set {
$this->content = $value;
$this->markPropertyAsChanged('content');
}
}

public ?int $user_id {
get => $this->user_id ?? null;
set {
$this->user_id = $value;
$this->markPropertyAsChanged('user_id');
}
}

// BelongsTo: Note belongs to User
#[BelongsTo(
related: User::class,
foreign_key: 'user_id',
local_key: 'id'
)]
public ?User $user {
get => $this->relations->getRelation('user');
}

// HasMany: Note has many Comments
#[HasMany(
related: Comment::class,
foreign_key: 'note_id',
local_key: 'id'
)]
public array $comments {
get => $this->relations->getRelation('comments');
}

// BelongsToMany: Note belongs to many Tags
#[BelongsToMany(
related: Tag::class,
pivot_table: 'note_tag',
foreign_pivot_key: 'note_id',
related_pivot_key: 'tag_id'
)]
public array $tags {
get => $this->relations->getRelation('tags');
}

public function attachTags(array $tagIds): void
{
$relation = $this->relations->getRelationInstance('tags');
$relation->attach($tagIds);
}
}
```

## Query Builder

Access the query builder through the static `query()` method:

```php
// Find by ID
$note = Note::query()->find(1);

// Get all records
$notes = Note::query()->get();

// Where clause
$notes = Note::query()
 ->where('user_id', '=', 1)
 ->get();

// Order and limit
$notes = Note::query()
 ->where('published', '=', true)
 ->orderBy('created_at', 'DESC')
 ->limit(10)
 ->get();

// First record
$note = Note::query()
 ->where('slug', '=', 'hello-world')
 ->first();
```

## Creating and Updating

Use the `fill()` method for mass assignment:

```php
// Create new record
$note = new Note()->fill([
'title' => 'My Note',
'content' => 'Note content here',
'user_id' => 1,
]);
$note->save();

// Update existing record
$note = Note::query()->find(1);
$note->title = 'Updated Title';
$note->save();
```

## Next Steps

- [Learn how to use models in controllers →](/docs/controllers)

- [Learn about DTO validation →](/docs/validation)
