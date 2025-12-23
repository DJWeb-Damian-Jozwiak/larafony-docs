---
title: "Smarty Template Bridge"
description: "Use Smarty templating engine as an alternative to Blade."
---

# Smarty Template Bridge

## Installation

```bash
composer require larafony/view-smarty
```

## Configuration

```php
use Larafony\View\Smarty\ServiceProviders\SmartyServiceProvider;

$app->withServiceProviders([
SmartyServiceProvider::class
]);
```

## Template Example

```smarty
{* resources/views/smarty/welcome.tpl *}
{extends file="layout.tpl"}

{block name="title"}Welcome{/block}

{block name="content"}
<h1>Hello {$name}!</h1>

{if $user->isAdmin()}

You have admin access.

{/if}

{foreach $items as $item}
- {$item.name} - {$item.price|number_format:2}

{/foreach}

{/block}
```

## Usage in Controller

```php
use Larafony\View\Smarty\SmartyRenderer;

final class HomeController extends Controller
{
#[Route('/', methods: ['GET'])]
public function index(SmartyRenderer $smarty): ResponseInterface
{
$html = $smarty->render('welcome.tpl', [
'name' => 'John',
'items' => Item::all(),
]);

return new Response(200, [], $html);
}
}
```

## Features

- **Template inheritance** - extends, block

- **Modifiers** - date_format, number_format, escape

- **Control structures** - if, foreach, include

- **Plugins** - Custom functions and modifiers

- **Caching** - Compiled template caching
