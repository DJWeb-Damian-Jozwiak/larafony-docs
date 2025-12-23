---
title: "Twig Template Bridge"
description: "Use Twig templating engine as an alternative to Blade."
---

# Twig Template Bridge

## Installation

```bash
composer require larafony/view-twig
```

## Configuration

```php
use Larafony\View\Twig\ServiceProviders\TwigServiceProvider;

$app->withServiceProviders([
TwigServiceProvider::class
]);
```

## Template Example

```twig
{# resources/views/twig/welcome.twig #}
{% extends "layout.twig" %}

{% block title %}Welcome{% endblock %}

{% block content %}
<h1>Hello {{ name }}!</h1>

{% if user.isAdmin %}

You have admin access.

{% endif %}

{% for item in items %}
- {{ item.name }} - {{ item.price|number_format(2) }}

{% endfor %}

{% endblock %}
```

## Usage in Controller

```php
use Larafony\View\Twig\TwigRenderer;

final class HomeController extends Controller
{
#[Route('/', methods: ['GET'])]
public function index(TwigRenderer $twig): ResponseInterface
{
$html = $twig->render('welcome.twig', [
'name' => 'John',
'items' => Item::all(),
]);

return new Response(200, [], $html);
}
}
```

## Features

- **Template inheritance** - extends, block, parent

- **Filters** - date, number_format, escape, etc.

- **Control structures** - if, for, include

- **Macros** - Reusable template functions

- **Auto-escaping** - XSS protection by default
