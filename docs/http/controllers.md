---
title: "Controllers & Routing"
description: "Define routes with PHP attributes and create RESTful controllers"
---

# Controllers & Routing

## Creating a Controller

Controllers in Larafony extend the `Controller` base class and use the `#[Route]` attribute
to define routes directly on methods.

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Larafony\Framework\Routing\Advanced\Attributes\Route;
use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController extends Controller
{
#[Route('/', 'GET')]
public function index(ServerRequestInterface $request): ResponseInterface
{
return $this->render('home', [
'title' => 'Welcome to Larafony'
]);
}
}
```

> **Info:** **Auto-Discovery:** Routes are automatically discovered from the `src/Controllers` directory. No need to manually register routes!

## Route Attributes

The `#[Route]` attribute accepts a path and HTTP method:

```php
#[Route('/notes', 'GET')]
public function index(): ResponseInterface
{
// GET /notes
}

#[Route('/notes', 'POST')]
public function store(): ResponseInterface
{
// POST /notes
}

#[Route('/notes/<id:\d+>', 'GET')]
public function show(int $id): ResponseInterface
{
// GET /notes/123
}

#[Route('/notes/<id:\d+>', 'PUT')]
public function update(int $id): ResponseInterface
{
// PUT /notes/123
}

#[Route('/notes/<id:\d+>', 'DELETE')]
public function destroy(int $id): ResponseInterface
{
// DELETE /notes/123
}
```

## Route Parameters

Capture route parameters using angle brackets with optional patterns:

```php
#[Route('/users/<id:\d+>', 'GET')]
public function show(int $id): ResponseInterface
{
$user = User::query()->find($id);

return $this->render('users.show', ['user' => $user]);
}

#[Route('/posts/<slug:[a-z0-9\-]+>', 'GET')]
public function showBySlug(string $slug): ResponseInterface
{
$post = Post::query()
->where('slug', '=', $slug)
->first();

return $this->render('posts.show', ['post' => $post]);
}
```

### CommonRouteRegex Enum

Instead of writing regex patterns manually, you can use the `CommonRouteRegex` enum for common patterns:

```php
use Larafony\Framework\Routing\Advanced\Enums\CommonRouteRegex;

// Using enum value in route definition
#[Route('/users/<id:' . CommonRouteRegex::DIGITS->value . '>', 'GET')]
public function show(int $id): ResponseInterface
{
// GET /users/123
}

#[Route('/posts/<slug:' . CommonRouteRegex::SLUG->value . '>', 'GET')]
public function showBySlug(string $slug): ResponseInterface
{
// GET /posts/my-awesome-post
}

#[Route('/articles/<uuid:' . CommonRouteRegex::UUID->value . '>', 'GET')]
public function showByUuid(string $uuid): ResponseInterface
{
// GET /articles/550e8400-e29b-41d4-a716-446655440000
}

#[Route('/archive/<date:' . CommonRouteRegex::ISO_DATE->value . '>', 'GET')]
public function archive(string $date): ResponseInterface
{
// GET /archive/2025-01-15
}
```

**Available patterns in CommonRouteRegex:**

| Pattern | Value | Example Match |
|---------|-------|---------------|
| `DIGITS` | `\d+` | `123`, `42` |
| `UUID` | `[0-9a-f]{8}-...` | `550e8400-e29b-41d4-...` |
| `SLUG` | `[a-z0-9]+(?:-[a-z0-9]+)*` | `my-post-title` |
| `ALPHA` | `[a-zA-Z]+` | `Hello`, `world` |
| `ALPHA_LOWER` | `[a-z]+` | `hello` |
| `ALPHA_DASH` | `[a-zA-Z-]+` | `hello-world` |
| `ALPHA_NUM` | `[a-zA-Z0-9]+` | `abc123` |
| `ISO_DATE` | `\d{4}-\d{2}-\d{2}` | `2025-01-15` |
| `ISO_DATETIME` | `\d{4}-\d{2}-\d{2}T...` | `2025-01-15T10:30:00` |
| `EMAIL` | `[a-zA-Z0-9._%+-]+@...` | `user@example.com` |
| `USERNAME` | `[a-zA-Z0-9_-]{3,20}` | `john_doe` |
| `SEMVER` | `\d+\.\d+\.\d+` | `1.0.0` |
| `LOCALE` | `[a-z]{2}(?:_[A-Z]{2})?` | `en`, `en_US` |
| `CURRENCY` | `[A-Z]{3}` | `USD`, `EUR` |

> **Info:** Using `CommonRouteRegex` ensures consistency across your routes and prevents typos in regex patterns.

## Model Binding (Auto-Binding)

One of Larafony's most powerful features is automatic model binding. Using the `#[RouteParam]` attribute,
you can automatically resolve route parameters into model instances. The framework will fetch the model from the database
and inject it directly into your controller method.

### Basic Model Binding

Use `#[RouteParam]` to configure model binding:

```php
use App\Models\Note;
use Larafony\Framework\Routing\Advanced\Attributes\{Route, RouteParam};

#[Route('/notes/<note>', 'GET')]
#[RouteParam(name: 'note', pattern: '\d+', bind: Note::class)]
public function show(ServerRequestInterface $request, Note $note): ResponseInterface
{
// $note is automatically loaded from database
// using the route parameter value

return $this->render('notes.show', ['note' => $note]);
}
```

> **Success:** **How it works:** When you visit `/notes/123`, Larafony: Validates the parameter matches the pattern (`\d+`) Calls `Note::findForRoute(123)` to load the model Injects the loaded model into your controller method Returns 404 automatically if the model is not found!

### Model findForRoute Method

Your model must implement the `findForRoute()` method for binding to work:

```php
use Larafony\Framework\Database\ORM\Model;

class Note extends Model
{
public string $table { get => 'notes'; }

public static function findForRoute(int|string $id): ?static
{
return static::query()->find($id);
}
}
```

### Before and After Comparison

See how model binding simplifies your code:

```php
// ❌ Without Model Binding (manual approach)
#[Route('/notes/<id:\d+>', 'GET')]
public function show(ServerRequestInterface $request): ResponseInterface
{
$params = $request->getAttribute('routeParams');
$note = Note::query()->find($params['id']);

if (!$note) {
// Handle 404
return new Response(404, [], 'Note not found');
}

return $this->render('notes.show', ['note' => $note]);
}

// ✅ With Model Binding (automatic approach)
#[Route('/notes/<note>', 'GET')]
#[RouteParam(name: 'note', pattern: '\d+', bind: Note::class)]
public function show(ServerRequestInterface $request, Note $note): ResponseInterface
{
// $note is already loaded, 404 handled automatically!

return $this->render('notes.show', ['note' => $note]);
}
```

### Custom Resolution Methods

Use `findMethod` parameter to specify a custom resolution method (e.g., find by slug instead of ID):

```php
// In your model
class Post extends Model
{
public static function findBySlug(string $slug): ?static
{
return static::query()->where('slug', '=', $slug)->first();
}
}

// In your controller
#[Route('/posts/<slug:[a-z0-9-]+>', 'GET')]
#[RouteParam(name: 'slug', bind: Post::class, findMethod: 'findBySlug')]
public function show(ServerRequestInterface $request, Post $post): ResponseInterface
{
// $post resolved via findBySlug() instead of default findForRoute()
// Pattern validation ([a-z0-9-]+) and binding in the same attribute!

return $this->render('posts.show', ['post' => $post]);
}
```

### Multiple Model Bindings

You can bind multiple models in a single route:

```php
use App\Models\User;
use App\Models\Note;

#[Route('/users/<user>/notes/<note>', 'GET')]
#[RouteParam(name: 'user', pattern: '\d+', bind: User::class)]
#[RouteParam(name: 'note', pattern: '\d+', bind: Note::class)]
public function showUserNote(
ServerRequestInterface $request,
User $user,
Note $note
): ResponseInterface {
// Both models are automatically loaded!
// $user is loaded from <user> parameter
// $note is loaded from <note> parameter

return $this->render('users.notes.show', [
'user' => $user,
'note' => $note
]);
}
```

### Combining Model Binding with DTOs

Mix model binding with DTO injection for powerful update operations:

```php
use App\Models\Note;
use App\DTOs\UpdateNoteDto;

#[Route('/notes/<note>', 'PUT')]
#[RouteParam(name: 'note', pattern: '\d+', bind: Note::class)]
public function update(
ServerRequestInterface $request,
Note $note,
UpdateNoteDto $dto
): ResponseInterface {
// $note is auto-bound from route parameter
// $dto is auto-created and validated from request body

$note->title = $dto->title;
$note->content = $dto->content;
$note->save();

return $this->redirect("/notes/{$note->id}");
}
```

### Working with Relationships

Auto-bound models work seamlessly with relationships:

```php
#[Route('/notes/<note>', 'GET')]
#[RouteParam(name: 'note', pattern: '\d+', bind: Note::class)]
public function show(ServerRequestInterface $request, Note $note): ResponseInterface
{
// Access relationships directly
$author = $note->user;
$tags = $note->tags;
$comments = $note->comments;

return $this->render('notes.show', [
'note' => $note,
'author' => $author,
'tags' => $tags,
'comments' => $comments
]);
}
```

### Complete CRUD with Model Binding

Here's a complete CRUD controller using model binding:

```php
use App\Models\Note;
use App\DTOs\{CreateNoteDto, UpdateNoteDto};
use Larafony\Framework\Routing\Advanced\Attributes\{Route, RouteParam};

class NoteController extends Controller
{
// List all notes (no binding needed)
#[Route('/notes', 'GET')]
public function index(ServerRequestInterface $request): ResponseInterface
{
$notes = Note::query()->get();
return $this->render('notes.index', ['notes' => $notes]);
}

// Show single note (model binding)
#[Route('/notes/<note>', 'GET')]
#[RouteParam(name: 'note', pattern: '\d+', bind: Note::class)]
public function show(ServerRequestInterface $request, Note $note): ResponseInterface
{
return $this->render('notes.show', ['note' => $note]);
}

// Show edit form (model binding)
#[Route('/notes/<note>/edit', 'GET')]
#[RouteParam(name: 'note', pattern: '\d+', bind: Note::class)]
public function edit(ServerRequestInterface $request, Note $note): ResponseInterface
{
return $this->render('notes.edit', ['note' => $note]);
}

// Update note (model binding + DTO)
#[Route('/notes/<note>', 'PUT')]
#[RouteParam(name: 'note', pattern: '\d+', bind: Note::class)]
public function update(
ServerRequestInterface $request,
Note $note,
UpdateNoteDto $dto
): ResponseInterface {
$note->title = $dto->title;
$note->content = $dto->content;
$note->save();

return $this->redirect("/notes/{$note->id}");
}

// Delete note (model binding)
#[Route('/notes/<note>', 'DELETE')]
#[RouteParam(name: 'note', pattern: '\d+', bind: Note::class)]
public function destroy(ServerRequestInterface $request, Note $note): ResponseInterface
{
$note->delete();
return $this->redirect('/notes');
}
}
```

> **Info:** **Route Parameter Syntax:** Larafony uses `&lt;param&gt;` or `&lt;param:pattern&gt;` syntax for route parameters, not `{param}`. This allows inline regex patterns like `&lt;id:\d+&gt;` or `&lt;slug:[a-z0-9-]+&gt;`.

## DTO Injection

Type-hint a DTO class to automatically validate and hydrate request data:

```php
use App\DTOs\CreateNoteDto;

#[Route('/notes', 'POST')]
public function store(CreateNoteDto $dto): ResponseInterface
{
// $dto is automatically created from request
// and validated based on attributes

$note = new Note()->fill([
'title' => $dto->title,
'content' => $dto->content,
]);
$note->save();

return $this->redirect('/notes');
}
```

See the [DTO Validation](/docs/validation) guide for more details.

## Response Helpers

The `Controller` base class provides helpful methods for creating responses:

### Rendering Views

```php
// Render a Blade template
return $this->render('notes.index', [
'notes' => $notes
]);
```

### JSON Responses

```php
// Return JSON
return $this->json([
'success' => true,
'data' => $notes
]);

// With status code
return $this->json(['error' => 'Not found'], 404);
```

### Redirects

```php
// Redirect to a URL
return $this->redirect('/notes');

// Redirect with status code
return $this->redirect('/login', 302);
```

## URL Generation

Generate URLs from named routes using the `UrlGenerator` class. This approach is preferred over
hardcoding URLs because it automatically handles route changes and parameter substitution.

### Named Routes

First, add names to your routes using the `name` parameter:

```php
use Larafony\Framework\Routing\Advanced\Attributes\Route;

class NoteController extends Controller
{
#[Route('/notes', 'GET', name: 'notes.index')]
public function index(): ResponseInterface { /* ... */ }

#[Route('/notes/<id:\d+>', 'GET', name: 'notes.show')]
public function show(int $id): ResponseInterface { /* ... */ }

#[Route('/notes/<id:\d+>/edit', 'GET', name: 'notes.edit')]
public function edit(int $id): ResponseInterface { /* ... */ }
}
```

### Using UrlGenerator

Inject the `UrlGenerator` via dependency injection and generate URLs:

```php
use Larafony\Framework\Routing\Advanced\UrlGenerator;
use Larafony\Framework\Routing\Advanced\Attributes\Route;

class NoteController extends Controller
{
public function __construct(
private readonly UrlGenerator $urlGenerator,
) {}

#[Route('/notes', 'POST', name: 'notes.store')]
public function store(CreateNoteDto $dto): ResponseInterface
{
$note = new Note()->fill($dto->toArray());
$note->save();

// Generate URL from named route with parameters
$url = $this->urlGenerator->route('notes.show', ['id' => $note->id]);
// Result: /notes/123

return $this->redirect($url);
}
}
```

### Absolute URLs

Generate absolute URLs (including domain) for emails, external links, or API responses:

```php
// Relative URL (default)
$url = $this->urlGenerator->route('notes.show', ['id' => 42]);
// Result: /notes/42

// Absolute URL
$url = $this->urlGenerator->route('notes.show', ['id' => 42], absolute: true);
// Result: https://example.com/notes/42

// Shorthand method for absolute URLs
$url = $this->urlGenerator->routeAbsolute('notes.show', ['id' => 42]);
// Result: https://example.com/notes/42
```

> **Info:** **Configuration:** The base URL for absolute URLs is read from `config/app.php` under the `url` key. Set this to your application's domain.

### Query String Parameters

Extra parameters that don't match route placeholders become query string parameters:

```php
$url = $this->urlGenerator->route('notes.index', [
'page' => 2,
'sort' => 'created_at',
'order' => 'desc',
]);
// Result: /notes?page=2&sort=created_at&order=desc
```

### Using Router::generate()

Alternatively, use the `Router::generate()` method directly:

```php
use Larafony\Framework\Routing\Advanced\Router;

class NoteController extends Controller
{
public function __construct(
private readonly Router $router,
) {}

#[Route('/notes/<id:\d+>/duplicate', 'POST')]
public function duplicate(int $id): ResponseInterface
{
$original = Note::query()->find($id);
$copy = $original->replicate()->save();

$url = $this->router->generate('notes.edit', ['id' => $copy->id]);

return $this->redirect($url);
}
}
```

### URL Generation in Services

Inject `UrlGenerator` into services for generating URLs outside controllers:

```php
use Larafony\Framework\Routing\Advanced\UrlGenerator;

class NotificationService
{
public function __construct(
private readonly UrlGenerator $urlGenerator,
private readonly MailerContract $mailer,
) {}

public function sendNoteSharedNotification(Note $note, User $recipient): void
{
$viewUrl = $this->urlGenerator->routeAbsolute('notes.show', [
'id' => $note->id,
]);

$this->mailer->send(new NoteSharedMail($note, $recipient, $viewUrl));
}
}
```

## Complete CRUD Example

Here's a complete RESTful controller for managing notes:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Note;
use App\DTOs\CreateNoteDto;
use App\DTOs\UpdateNoteDto;
use Larafony\Framework\Routing\Advanced\Attributes\{Route, RouteParam};
use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;

class NoteController extends Controller
{
#[Route('/notes', 'GET')]
public function index(): ResponseInterface
{
$notes = Note::query()->get();

return $this->render('notes.index', ['notes' => $notes]);
}

#[Route('/notes/create', 'GET')]
public function create(): ResponseInterface
{
return $this->render('notes.create');
}

#[Route('/notes', 'POST')]
public function store(CreateNoteDto $dto): ResponseInterface
{
$note = new Note()->fill([
'title' => $dto->title,
'content' => $dto->content,
'user_id' => 1, // Get from auth
]);
$note->save();

return $this->redirect('/notes');
}

#[Route('/notes/<note:\d+>', 'GET')]
#[RouteParam(name: 'note', bind: Note::class)]
public function show(Note $note): ResponseInterface
{
return $this->render('notes.show', ['note' => $note]);
}

#[Route('/notes/<note:\d+>/edit', 'GET')]
#[RouteParam(name: 'note', bind: Note::class)]
public function edit(Note $note): ResponseInterface
{
return $this->render('notes.edit', ['note' => $note]);
}

#[Route('/notes/<note:\d+>', 'PUT')]
#[RouteParam(name: 'note', bind: Note::class)]
public function update(Note $note, UpdateNoteDto $dto): ResponseInterface
{
$note->title = $dto->title;
$note->content = $dto->content;
$note->save();

return $this->redirect("/notes/{$note->id}");
}

#[Route('/notes/<note:\d+>', 'DELETE')]
#[RouteParam(name: 'note', bind: Note::class)]
public function destroy(Note $note): ResponseInterface
{
$note->delete();

return $this->redirect('/notes');
}
}
```

## API Controllers

Create JSON APIs by returning JSON responses:

```php
<?php

namespace App\Controllers;

use App\Models\Note;
use Larafony\Framework\Routing\Advanced\Attributes\{Route, RouteParam};
use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;

class ApiNoteController extends Controller
{
#[Route('/api/notes', 'GET')]
public function index(): ResponseInterface
{
$notes = Note::query()->get();

return $this->json([
'success' => true,
'data' => $notes
]);
}

#[Route('/api/notes/<note:\d+>', 'GET')]
#[RouteParam(name: 'note', bind: Note::class)]
public function show(Note $note): ResponseInterface
{
return $this->json([
'success' => true,
'data' => $note
]);
}
}
```

## Next Steps

- [Learn about DTO validation →](/docs/validation)

- [Learn about adding middleware to routes →](/docs/middleware)

- [Learn about using models →](/docs/models)
