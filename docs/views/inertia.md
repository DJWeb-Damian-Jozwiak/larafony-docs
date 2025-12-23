---
title: "Inertia.js & Vue Integration"
description: "Build modern single-page applications using server-side routing with Vue.js components, powered by Inertia.js."
---

# Inertia.js & Vue Integration

> **Info:** **PSR-15 Middleware:** Inertia.js integration uses PSR-15 middleware for shared props and request handling.

## Overview

Larafony's Inertia.js integration allows you to build SPAs without the complexity of traditional client-side routing:

- **Server-Side Routing** - Use Larafony's attribute-based routes

- **No API Required** - Return data directly from controllers

- **Vue 3 Components** - Build UI with modern Vue composition API

- **Automatic XHR/HTML Detection** - Seamless initial load and navigation

- **Shared Props** - Global data available to all components

- **Vite Integration** - Hot module replacement in development

## Basic Setup

### Root View Template

Create the Inertia root template at `resources/views/blade/inertia.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Larafony App</title>

<!-- Vite Assets -->
{{ "@" }}vite(['resources/js/app.js'])
</head>
<body>
<!-- Inertia App Container -->

'>

```

> **Warning:** **Important:** The `$page` variable contains Inertia page data (component, props, url, version) and is automatically passed by the framework.

### Controller with Inertia

```php
use Larafony\Framework\Routing\Advanced\Attributes\Route;
use Larafony\Framework\Routing\Advanced\Attributes\RouteParam;
use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;

class NotesController extends Controller
{
#[Route('/notes', 'GET')]
public function index(): ResponseInterface
{
$notes = Note::query()->get();

// Transform to array for JSON serialization
$notesData = array_map(function ($note) {
return [
'id' => $note->id,
'title' => $note->title,
'content' => $note->content,
'user' => [
'name' => $note->user->name ?? 'Unknown',
],
];
}, $notes);

return $this->inertia('Notes/Index', [
'notes' => $notesData,
]);
}

#[Route('/notes/<note:\d>', 'GET')]
#[RouteParam(name: 'note', bind: Note::class)]
public function show(ServerRequestInterface $request, Note $note): ResponseInterface
{
return $this->inertia('Notes/Show', [
'note' => [
'id' => $note->id,
'title' => $note->title,
'content' => $note->content,
],
]);
}
}
```

### Vue Component

Create Vue components in `resources/js/Pages/`:

```vue
<!-- resources/js/Pages/Notes/Index.vue -->
<template>

<h1>Notes</h1>
<Link href="/notes/create" class="btn btn-primary">
Create New Note
</Link>

No notes found. Create your first note!

<h5 class="card-title">{{ note.title }}</h5>

{{ truncate(note.content, 100) }}

<Link
:href="`/notes/${note.id}`"
class="btn btn-sm btn-outline-primary"
>
View Details

<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
 notes: Array,
})

const truncate = (text, length) => {
 if (text.length <= length) return text
 return text.substring(0, length) + '...'
}
</script>
```

## Shared Props with Middleware

Share global data across all Inertia responses using custom middleware:

```php
use Larafony\Framework\Http\Middleware\InertiaMiddleware as BaseInertiaMiddleware;
use Psr\Http\Message\ServerRequestInterface;

class AppInertiaMiddleware extends BaseInertiaMiddleware
{
/**
* Share data globally with all Inertia responses
*/
protected function getSharedData(ServerRequestInterface $request): array
{
return [
'auth' => [
'user' => $this->getAuthenticatedUser($request),
],
// Lazy evaluation - only computed when accessed
'flash' => fn() => $this->getFlashMessages(),
'errors' => fn() => $this->getValidationErrors(),
];
}

private function getAuthenticatedUser(ServerRequestInterface $request): ?array
{
// Your authentication logic
return [
'id' => 1,
'name' => 'John Doe',
'email' => 'john@example.com',
];
}

private function getFlashMessages(): array
{
// Flash message logic
return ['success' => 'Operation completed'];
}

private function getValidationErrors(): array
{
// Validation error logic
return [];
}
}
```

### Accessing Shared Props in Vue

```vue
<script setup>
import { usePage } from '@inertiajs/vue3'

const page = usePage()

// Access shared props
const user = page.props.auth.user
const flash = page.props.flash
</script>

<template>

Welcome, {{ user.name }}

{{ flash.success }}

</template>
```

## Navigation with Inertia

### Using Link Component

```vue
<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
 <!-- Standard navigation -->
 <Link href="/notes">All Notes</Link>

 <!-- With HTTP method -->
 <Link href="/notes/1" method="delete" as="button">
Delete
 </Link>

 <!-- Preserve scroll position -->
 <Link href="/notes?page=2" preserve-scroll>
Next Page

```

### Programmatic Navigation

```vue
<script setup>
import { router } from '@inertiajs/vue3'

const deleteNote = (noteId) => {
 if (confirm('Are you sure?')) {
router.delete(`/notes/${noteId}`, {
onSuccess: () => {
console.log('Note deleted')
},
onError: (errors) => {
console.error('Failed to delete', errors)
}
})
 }
}

const createNote = (data) => {
 router.post('/notes', data, {
preserveScroll: true,
onSuccess: () => {
router.visit('/notes')
}
 })
}
</script>
```

## Vite Configuration

The `{{ "@" }}vite` directive automatically handles asset loading:

```blade
<!-- Development: Loads from Vite dev server (localhost:5173) -->
<!-- Production: Loads from manifest.json -->
{{ "@" }}vite(['resources/js/app.js', 'resources/css/app.css'])
```

### Example Vite Entry Point

```javascript
// resources/js/app.js
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'

createInertiaApp({
 resolve: (name) => {
const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
return pages[`./Pages/${name}.vue`]
 },
 setup({ el, App, props, plugin }) {
createApp({ render: () => h(App, props) })
.use(plugin)
.mount(el)
 },
})
```

## Key Features

### Lazy Prop Evaluation

Use closures for expensive computations that only run when needed:

```php
return $this->inertia('Dashboard', [
'stats' => [
'users' => User::count(),
'posts' => Post::count(),
],
// Only computed on partial reloads requesting 'analytics'
'analytics' => fn() => $this->computeExpensiveAnalytics(),
]);
```

### Partial Reloads

Request only specific props to optimize data transfer:

```vue
<Link href="/dashboard" only="['stats']">
 Refresh Stats
</Link>
```

### Asset Versioning

Configure asset version for cache invalidation:

```php
// In service provider or middleware
$inertia = app(Inertia::class);
$inertia->version(md5_file(public_path('build/manifest.json')));
```

## Full Example: CRUD with Relationships

```php
class NotesController extends Controller
{
#[Route('/notes', 'GET')]
public function index(): ResponseInterface
{
$notes = Note::query()->get();

return $this->inertia('Notes/Index', [
'notes' => array_map(fn($note) => [
'id' => $note->id,
'title' => $note->title,
'content' => $note->content,
'user' => [
'name' => $note->user->name ?? 'Unknown',
],
'tags' => array_map(
fn($tag) => ['id' => $tag->id, 'name' => $tag->name],
$note->tags ?? []
),
], $notes),
]);
}

#[Route('/notes/<note:\d>', 'GET')]
#[RouteParam(name: 'note', bind: Note::class)]
public function show(ServerRequestInterface $request, Note $note): ResponseInterface
{
return $this->inertia('Notes/Show', [
'note' => [
'id' => $note->id,
'title' => $note->title,
'content' => $note->content,
'user' => [
'id' => $note->user->id,
'name' => $note->user->name,
],
'tags' => array_map(
fn($tag) => ['id' => $tag->id, 'name' => $tag->name],
$note->tags ?? []
),
'comments' => array_map(
fn($c) => [
'id' => $c->id,
'content' => $c->content,
'user' => ['name' => $c->user->name ?? 'Anonymous'],
],
$note->comments ?? []
),
],
]);
}
}
```

#### Key Differences from Laravel

- **PSR-15 Middleware:** Larafony uses standard PSR-15 middleware instead of Laravel's proprietary middleware

- **Attribute-Based Routing:** Routes defined with PHP attributes on controller methods, not in route files

- **No Magic Directives:** Uses standard PHP for `$page` serialization instead of custom Blade directives

- **Type-Safe:** Full PHP 8.5 type hints and model binding with `#[RouteParam]`

- **Built-in Feature:** Inertia.js integration is part of the framework core, not a separate package

**Demo App:** See the full Inertia.js implementation with CRUD operations, relationships, and Vue components in action.

[
View on Packagist
](https://packagist.org/packages/larafony/skeleton)
[
View on GitHub
](https://github.com/DJWeb-Damian-Jozwiak/larafony-demo-app)
