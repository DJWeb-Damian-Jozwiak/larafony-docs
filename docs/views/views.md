---
title: "Views & Blade Templates"
description: "Build dynamic views with Larafony\'s Blade-inspired template engine featuring components, layouts, and directives."
---

# Views & Blade Templates

> **Info:** **PSR-7 Integration:** Views extend PSR-7 Response, making them directly returnable from controllers.

## Overview

Larafony's template system provides:

- **Blade Syntax** - Familiar directives like {{ "@" }}if, {{ "@" }}foreach, {{ "@" }}extends

- **Component System** - Reusable UI components with slots

- **Template Inheritance** - Layouts and sections

- **Compiled Templates** - Cached for performance

- **PSR-7 Compatible** - Return directly from controllers

## Basic View Usage

### Returning Views from Controllers

```php
use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;

class HomeController extends Controller
{
public function index(): ResponseInterface
{
// Renders resources/views/home.blade.php
return $this->render('home', [
'title' => 'Welcome',
'user' => $user,
]);
}
}
```

### View File Structure

```bash
resources/
└── views/
└── blade/
├── home.blade.php
├── layouts/
│ └── app.blade.php
├── components/
│ ├── alert.blade.php
│ └── card.blade.php
└── posts/
├── index.blade.php
└── show.blade.php
```

## Blade Syntax

### Displaying Data

```blade
{{-- Escaped output (safe) --}}
{{ $name }}
{{ $user->email }}

{{-- Raw output (use with caution) --}}
{!! $htmlContent !!}

{{-- Comments (won't appear in HTML) --}}
{{-- This is a comment --}}
```

### Control Structures

```blade
{{-- If statements --}}
{{ "@" }}if($user->isAdmin())

Welcome, Admin!

{{ "@" }}elseif($user->isModerator())

Welcome, Moderator!

{{ "@" }}else

Welcome, User!

{{ "@" }}endif

{{-- Unless (inverse if) --}}
{{ "@" }}unless($user->isBanned())

You can post comments

{{ "@" }}endunless

{{-- Isset check --}}
{{ "@" }}isset($user)

User: {{ $user->name }}

{{ "@" }}endisset

{{-- Empty check --}}
{{ "@" }}empty($posts)

No posts found

{{ "@" }}endempty
```

### Loops

```blade
{{-- Foreach loop --}}
{{ "@" }}foreach($posts as $post)
<article>

## {{ $post->title }}

{{ $post->excerpt }}

</article>
{{ "@" }}endforeach

{{-- For loop --}}
{{ "@" }}for($i = 0; $i < 10; $i++)

Item {{ $i }}

{{ "@" }}endfor

{{-- While loop --}}
{{ "@" }}while($condition)

Processing...

{{ "@" }}endwhile
```

## Layouts & Sections

### Defining a Layout

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $title ?? 'Larafony' }}</title>
 - 
{{ "@" }}stack('styles')
</head>
<body>
<header>
<h1>{{ $title }}</h1>
<nav>
[Home](/)
[About](/about)

<main>
{{ "@" }}yield('content')
</main>

<footer>

&copy; 2025 Larafony

</footer>

{{ "@" }}stack('scripts')

```

### Extending a Layout

```blade
{{-- resources/views/posts/show.blade.php --}}
{{ "@" }}extend('layouts.app')

{{ "@" }}section('content')
<article>

## {{ $post->title }}

By {{ $post->author }} on {{ $post->created_at }}

{!! $post->content !!}

{{ "@" }}if($post->tags)

{{ "@" }}foreach($post->tags as $tag)
{{ $tag }}
{{ "@" }}endforeach

{{ "@" }}endif
</article>
{{ "@" }}endsection

{{ "@" }}push('scripts')

{{ "@" }}endpush
```

## Components (The Power of Larafony)

### Creating a Component Class

```php
<?php
// app/View/Components/Alert.php

namespace App\View\Components;

use Larafony\Framework\View\Component;

class Alert extends Component
{
public function __construct(
public readonly string $type = 'info',
public readonly bool $dismissible = false,
public readonly ?string $title = null,
) {}

protected function getView(): string
{
return 'components.alert';
}
}
```

### Component Template

```blade
{{-- resources/views/components/alert.blade.php --}}

{{ "@" }}if($dismissible)
<button type="button" class="close">&times;</button>
{{ "@" }}endif

{{ "@" }}if($title)

#### {{ $title }}

{{ "@" }}endif

{!! $slot !!}

```

### Using Components

```blade
{{-- Simple usage --}}
<x-alert type="success">
Operation completed successfully!
</x-alert>

{{-- With attributes --}}
<x-alert type="warning" :dismissible="true" title="Warning">
Please review your settings.
</x-alert>

{{-- Dynamic attributes --}}
<x-alert :type="$messageType" :title="$messageTitle">
{{ $messageContent }}
</x-alert>
```

## Component Slots

### Named Slots

```php
// app/View/Components/Card.php
class Card extends Component
{
public function __construct(
public readonly ?string $title = null,
) {}

protected function getView(): string
{
return 'components.card';
}
}
```

```blade
{{-- resources/views/components/card.blade.php --}}

{{ "@" }}isset($slots['header'])

{!! $slots['header'] !!}

{{ "@" }}endisset

{{ "@" }}if($title)

### {{ $title }}

{{ "@" }}endif

{!! $slot !!}

{{ "@" }}isset($slots['footer'])

{!! $slots['footer'] !!}

{{ "@" }}endisset

```

### Using Named Slots

```blade
<x-card title="User Profile">
<x-slot:header>
<img src="{{ $user->avatar }}" alt="Avatar">
</x-slot:header>

Name: {{ $user->name }}

Email: {{ $user->email }}

<x-slot:footer>
<button>Edit Profile
</x-card>
```

## Advanced Component Examples

### Example 1: Button Component

```php
// app/View/Components/Button.php
class Button extends Component
{
public function __construct(
public readonly string $type = 'button',
public readonly string $variant = 'primary',
public readonly bool $disabled = false,
) {}

protected function getView(): string
{
return 'components.button';
}
}
```

```blade
{{-- resources/views/components/button.blade.php --}}
<button
type="{{ $type }}"
class="btn btn-{{ $variant }}"
{{ $disabled ? 'disabled' : '' }}
>
{!! $slot !!}
</button>

{{-- Usage --}}
<x-button type="submit" variant="success">
Save Changes
</x-button>

<x-button variant="danger" :disabled="true">
Delete
</x-button>
```

### Example 2: Modal Component

```php
// app/View/Components/Modal.php
class Modal extends Component
{
public function __construct(
public readonly string $id,
public readonly string $size = 'md',
) {}

protected function getView(): string
{
return 'components.modal';
}
}
```

```blade
{{-- resources/views/components/modal.blade.php --}}

{{ "@" }}isset($slots['header'])

{!! $slots['header'] !!}
<button class="close">&times;</button>

{{ "@" }}endisset

{!! $slot !!}

{{ "@" }}isset($slots['footer'])

{!! $slots['footer'] !!}

{{ "@" }}endisset

{{-- Usage --}}
<x-modal id="confirmDelete" size="sm">
<x-slot:header>
<h5>Confirm Deletion

Are you sure you want to delete this item?

<x-slot:footer>
<x-button variant="secondary">Cancel</x-button>
<x-button variant="danger">Delete
</x-modal>
```

## Asset Stacks

### Pushing to Stacks

```blade
{{-- In any view --}}
{{ "@" }}push('styles')
<link rel="stylesheet" href="/css/custom.css">
{{ "@" }}endpush

{{ "@" }}push('scripts')

<script>
initCharts();
</script>
{{ "@" }}endpush
```

### Rendering Stacks

```blade
{{-- In layout --}}
<head>
<link rel="stylesheet" href="/css/app.css">
{{ "@" }}stack('styles')
</head>
<body>
{{-- Content --}}

{{ "@" }}stack('scripts')
</body>
```

## Best Practices

#### Do

<li>Use `&lbrace;&lbrace; &rbrace;&rbrace;` for displaying data (automatic escaping)

- Create reusable components for repeated UI patterns

- Use named slots for complex component layouts

- Keep component logic in the component class

- Use layouts for consistent page structure

- Push scripts to stacks for proper loading order

#### Don't

- Don't use `&lbrace;!! !!&rbrace;` for user input (XSS risk)

- Don't put business logic in views

- Don't create deeply nested components (3+ levels)

- Don't forget to escape user-provided content

## Security

> **Danger:** **XSS Protection:** Always use `&lbrace;&lbrace; &rbrace;&rbrace;` for user input. Only use `&lbrace;!! !!&rbrace;` for trusted content.

```blade
{{-- SAFE - Automatically escaped --}}

Welcome, {{ $user->name }}

{{-- DANGEROUS - Use only for trusted HTML --}}

{!! $trustedHtmlContent !!}

{{-- WRONG - Vulnerable to XSS --}}

{!! $userComment !!}

{{-- CORRECT --}}

{{ $userComment }}

```

## Next Steps

#### Controllers

Learn how to create controllers and return views with data.

[
Read Guide 
](/docs/controllers)

#### Validation

Validate user input with DTO validation.

[
Read Guide 
](/docs/validation)
